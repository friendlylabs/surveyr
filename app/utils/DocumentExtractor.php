<?php

namespace App\Utils;

class DocumentExtractor
{
    public const MAX_TEXT_LENGTH = 50000;

    /**
     * Extract plain text from a document file.
     *
     * @param string $path      Path to the file on disk.
     * @param string $extension Lowercase extension WITHOUT the dot (pdf|doc|docx).
     * @return string Plain-text content.
     */
    public static function extract(string $path, string $extension): string
    {
        switch (strtolower($extension)) {
            case 'docx': $text = self::extractDocx($path); break;
            case 'pdf':  $text = self::extractPdf($path);  break;
            case 'doc':  $text = self::extractDoc($path);  break;
            default:
                throw new \Exception("Unsupported file type: .$extension");
        }

        return self::sanitize($text);
    }

    private static function extractDocx(string $path): string
    {
        if (!class_exists('\ZipArchive')) {
            throw new \Exception('The ZipArchive PHP extension is required to parse DOCX files');
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \Exception('Could not open DOCX file');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new \Exception('DOCX archive is missing word/document.xml');
        }

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphs = $xpath->query('//w:p');
        $lines = [];
        foreach ($paragraphs as $p) {
            $texts = $xpath->query('.//w:t', $p);
            $line = '';
            foreach ($texts as $t) $line .= $t->nodeValue;
            if (trim($line) !== '') $lines[] = $line;
        }

        return implode("\n\n", $lines);
    }

    private static function extractPdf(string $path): string
    {
        $binary = self::resolveBinary('pdftotext');
        if (!$binary) {
            throw new \Exception('pdftotext is not available on this server. Install poppler-utils or convert the file to DOCX.');
        }
        $cmd = sprintf('%s -nopgbrk %s - 2>&1', escapeshellarg($binary), escapeshellarg($path));
        $output = shell_exec($cmd);
        if ($output === null || trim($output) === '') {
            throw new \Exception('Could not extract text from the PDF (it may be scanned/image-only)');
        }
        return $output;
    }

    private static function extractDoc(string $path): string
    {
        $binary = self::resolveBinary('antiword');
        if (!$binary) {
            throw new \Exception('Legacy .doc files require antiword. Please convert to DOCX or PDF and try again.');
        }
        $cmd = sprintf('%s %s 2>&1', escapeshellarg($binary), escapeshellarg($path));
        $output = shell_exec($cmd);
        if ($output === null || trim($output) === '') {
            throw new \Exception('Could not extract text from the .doc file');
        }
        return $output;
    }

    /**
     * Locate an executable. Tries the bare name (in case PHP's PATH carries it)
     * and falls back to `where`/`which` to resolve an absolute path.
     */
    private static function resolveBinary(string $name): ?string
    {
        $probe = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'which';
        $found = @shell_exec($probe . ' ' . escapeshellarg($name) . ' 2>&1');
        if (!is_string($found)) return null;

        foreach (preg_split('/\r?\n/', $found) as $line) {
            $line = trim($line);
            if ($line === '' || stripos($line, 'not found') !== false) continue;
            if (file_exists($line)) return $line;
        }
        return null;
    }

    private static function sanitize(string $text): string
    {
        $text = preg_replace("/\r\n/", "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim($text);

        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_TEXT_LENGTH) . "\n[...truncated]";
        }
        return $text;
    }
}
