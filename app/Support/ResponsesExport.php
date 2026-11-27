<?php

namespace App\Support;

use App\Models\Form;
use App\Models\Submission;
use DateTimeImmutable;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\DateTimeCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Properties;
use OpenSpout\Writer\XLSX\Writer;

/**
 * ResponsesExport
 *
 * A form's responses as a styled Excel workbook (OpenSpout, written row by row so a big form doesn't fill memory).
 * The Responses sheet starts at its first row, so it sorts, filters and pivots as is: a brand-coloured heading row with
 * filters, frozen, then one row per response (dates and numbers as real values, rows banded). With details, each row
 * starts with the response's reference (linked to it in the app, and frozen), when it came in, who collected it and
 * from where; without, it holds only the answers. The About sheet says what was exported.
 * Files are never linked publicly. Exported with attachments (ExportResponses), every file is gathered into the folder
 * the zip is made from (AttachmentsExport, under attachments/, with its manifest) and each file question's cell links
 * to its file there by path, so the links work once the zip is unpacked. Otherwise a file's link goes to the app (sign-in and access to the form needed), which hands out a
 * short-lived signed URL (FormResponsesController@file).
 */
class ResponsesExport
{
    /** Brand green (--color-pc-brand), the banding grey (--color-pc-surface-alt) and the soft border (--color-pc-border-soft). */
    const BRAND = '19B394';

    const BAND = 'F5F7FA';

    const LINE = 'E3E6ED';

    /** Column widths (characters) for the fixed columns, then the questions by kind. */
    const WIDTHS = ['reference' => 12, 'submitted' => 19, 'collector' => 20, 'ip' => 16, 'text' => 30, 'rating' => 11, 'number' => 12, 'file' => 28];

    /** @var list<array{name: string, title: string, type: string, kind: string, max: int|null, choices: array<string, string>}> */
    private array $questions;

    /**
     * The cell styles, made once: OpenSpout tells styles apart by object, so each look must be one object kept alive for
     * the whole write (a style made per cell could be freed and its slot reused by a different one).
     *
     * @var array{heading: Style, title: Style, label: Style, value: Style, rows: array<int, array{text: Style, date: Style, link: Style, reference: Style}>}
     */
    private array $styles;

    /** Gathers the files into the folder, when exporting with attachments. */
    private ?AttachmentsExport $attachments = null;

    /**
     * @param  bool  $details  start each row with the reference, submitted, collected by and IP address columns
     * @param  string|null  $folder  write the files into this folder and link them by path (an export with attachments)
     * @param  string|null  $author  who the workbook says made it
     */
    public function __construct(private Form $form, private bool $details = true, ?string $folder = null, private ?string $author = null)
    {
        $this->questions = SurveyAnswers::questions($form->content);
        $this->styles = $this->styles();
        if ($folder !== null) {
            $this->attachments = new AttachmentsExport(AttachmentsExport::questions($form->content), $folder, 'attachments/');
        }
    }

    /**
     * Writes the workbook to a temporary file and returns its path.
     *
     * @param  iterable<Submission>  $responses
     * @param  array<string, string>  $about  label => value lines for the About sheet (what was exported, by whom)
     */
    public function write(iterable $responses, array $about = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'responses').'.xlsx';

        $writer = new Writer(new Options(
            FALLBACK_STYLE: new Style(fontSize: 10, fontName: 'Calibri'),
            properties: new Properties(title: $this->form->title, application: config('app.name'), creator: $this->author ?? config('app.name')),
        ));
        $writer->openToFile($path);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName(__('Responses'));
        $view = (new SheetView)->withFreezeRow(2)->withShowGridLines(false);
        $sheet->setSheetView($this->details ? $view->withFreezeColumn('B') : $view);
        foreach ($this->widths() as $column => $width) {
            $sheet->setColumnWidth($width, $column + 1);
        }

        $writer->addRow(new Row(array_map(fn (string $title) => new StringCell($title, $this->styles['heading']), $this->headings()), 30));

        $count = 0;
        foreach ($responses as $response) {
            $writer->addRow(new Row($this->cells($response, $this->styles['rows'][$count % 2])));
            $count++;
        }
        $sheet->setAutoFilter(new AutoFilter(0, 1, count($this->headings()) - 1, max(1, $count + 1)));
        $this->attachments?->writeManifest();

        $this->about($writer, $about + [__('Responses') => number_format($count)]);
        $writer->close();

        return $path;
    }

    /** The file name the download is offered as: the form's title and today's date. */
    public function filename(): string
    {
        return (str($this->form->title)->slug()->limit(60, '')->value() ?: 'responses').'-'.now()->format('Y-m-d').'.xlsx';
    }

    /** @return list<string> */
    private function headings(): array
    {
        $details = $this->details ? [__('Response'), __('Submitted'), __('Collected by'), __('IP address')] : [];

        return [...$details, ...array_column($this->questions, 'title')];
    }

    /** @return list<int> */
    private function widths(): array
    {
        $details = $this->details ? [self::WIDTHS['reference'], self::WIDTHS['submitted'], self::WIDTHS['collector'], self::WIDTHS['ip']] : [];

        return [...$details, ...array_map(fn (array $question) => self::WIDTHS[$question['kind']] ?? self::WIDTHS['text'], $this->questions)];
    }

    /**
     * One response's row: with details, its reference (linked to it in the app), when it came in, who collected it and
     * from where; then an answer per question.
     *
     * @return list<Cell>
     */
    private function cells(Submission $response, array $styles): array
    {
        $files = $this->attachments?->add($response) ?? [];
        $details = $this->details ? [
            $this->link(route('forms.responses.show', [$this->form, $response]), $response->reference, $styles['reference']),
            new DateTimeCell(DateTimeImmutable::createFromInterface($response->created_at), $styles['date']),
            new StringCell($response->collector?->name ?? '', $styles['text']),
            new StringCell((string) $response->ip_address, $styles['text']),
        ] : [];

        return [...$details, ...array_map(fn (array $question) => $this->answer($response, $question, $styles, $files[$question['name']] ?? []), $this->questions)];
    }

    /**
     * An answer's cell: ratings and numbers as numbers, files as links (see the class comment), the rest as text.
     *
     * @param  list<string>  $paths  where the question's files were gathered, when exporting with attachments
     */
    private function answer(Submission $response, array $question, array $styles, array $paths): Cell
    {
        $value = $response->data[$question['name']] ?? null;

        if (in_array($question['kind'], ['rating', 'number'], true) && is_numeric($value)) {
            return new NumericCell($value + 0, $styles['text']);
        }

        // a file question's files, or a signature pad's drawing
        if ($question['kind'] === 'file' && $value !== null && $value !== '' && $value !== []) {
            $names = SurveyAnswers::format($value, $question);
            if ($this->attachments !== null) {
                // one file links to itself; several to the folder holding them
                $target = count($paths) === 1 ? $paths[0] : ($paths ? dirname($paths[0]) : null);

                return $target ? $this->link($target, $names, $styles['link']) : new StringCell($names, $styles['text']);
            }
            // one file links to it; several link to the response, where each opens
            $url = ! is_array($value) || count($value) === 1
                ? route('forms.responses.file', [$this->form, $response, 'question' => $question['name'], 'file' => 0])
                : route('forms.responses.show', [$this->form, $response]);

            return $this->link($url, $names, $styles['link']);
        }

        return new StringCell(SurveyAnswers::format($value, $question), $styles['text']);
    }

    /** A clickable cell (a HYPERLINK formula; Excel shows the text, underlined in the brand colour, up to 255 characters). */
    private function link(string $url, string $text, Style $style): FormulaCell
    {
        $text = Str::limit($text, 250);
        $quote = fn (string $value) => '"'.str_replace('"', '""', $value).'"';

        return new FormulaCell('=HYPERLINK('.$quote($url).','.$quote($text).')', $text, $style);
    }

    /** The About sheet: the form, then what was exported. */
    private function about(Writer $writer, array $lines): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName(__('About'));
        $sheet->setSheetView((new SheetView)->withShowGridLines(false));
        $sheet->setColumnWidth(22, 1);
        $sheet->setColumnWidth(70, 2);

        $writer->addRow(new Row([new StringCell($this->form->title, $this->styles['title'])], 28));
        $writer->addRow(new Row([]));

        foreach ($lines as $name => $text) {
            $writer->addRow(new Row([new StringCell($name, $this->styles['label']), new StringCell($text, $this->styles['value'])]));
        }
    }

    /**
     * Every look the workbook uses: the brand heading, the About sheet's title, labels and values, and for plain and
     * banded rows their text, date, link and (bold link) reference cells.
     */
    private function styles(): array
    {
        $font = ['fontSize' => 10, 'fontName' => 'Calibri'];
        $rows = [];
        foreach ([null, self::BAND] as $band) {
            $text = new Style(...$font, cellVerticalAlignment: CellVerticalAlignment::CENTER, backgroundColor: $band,
                border: new Border(new BorderPart(BorderName::BOTTOM, self::LINE, BorderWidth::THIN, BorderStyle::SOLID)));
            $link = $text->withFontColor(self::BRAND)->withFontUnderline(true);
            $rows[] = ['text' => $text, 'date' => $text->withFormat('d mmm yyyy, hh:mm'), 'link' => $link, 'reference' => $link->withFontBold(true)];
        }

        return [
            'heading' => new Style(...$font, fontBold: true, fontColor: 'FFFFFF', cellVerticalAlignment: CellVerticalAlignment::CENTER, shouldWrapText: true, backgroundColor: self::BRAND),
            'title' => new Style(fontBold: true, fontSize: 16, fontName: 'Calibri', fontColor: self::BRAND),
            'label' => new Style(...$font, fontBold: true, fontColor: '6E7891'),
            'value' => new Style(...$font, shouldWrapText: true, cellVerticalAlignment: CellVerticalAlignment::TOP),
            'rows' => $rows,
        ];
    }
}
