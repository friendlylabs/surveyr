<?php

namespace App\Controllers\Base;

use App\Controllers\Controller;
use App\Models\Zone;
use Leaf\Database as DB;

class ZonesController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show all zones
     * 
     * @return void
     */
    public function index()
    {
        $this->zones = Zone::all();
        return $this->renderPage('Data Zones', 'app.zones.index');
    }

    /**
     * Show a single zone
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id)
    {
        $zone = Zone::find($id);
        if(!$zone) return $this->jsonError("Zone not found");

        $this->zone = $zone;
        return $this->renderPage($zone->name, 'app.zones.show');
    }

    /**
     * Store a new zone
     * 
     * @return void
     */
    public function store()
    {
        try{
            $data = [
                'name' => request()->params('name'),
                'description' => request()->params('description'),
                'user_id' => auth()->id()
            ];

            if(in_array(null, $data))
                return $this->jsonError("All fields are required");

            $zone = Zone::create($data);
            if(!$zone) return $this->jsonError("Failed to create zone");

            $this->redirect = route('zones.show', $zone->id);
            return $this->jsonSuccess("Zone created successfully");
        }

        catch(\Exception $e){
            return $this->jsonException($e);
        }
    }

    /**
     * Delete a zone
     * 
     * @param int $id
     * @return void
     */
    public function delete(int $id)
    {
        $zone = Zone::find($id);
        if(!$zone) 
            return redirect(route('zones.list'));

        $zone->delete();
        return redirect(route('zones.list'));
    }

    /**
     * Update a zone
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id)
    {
        $zone = Zone::find($id);
        if(!$zone) 
            return $this->jsonError("Zone not found");

        $data = [
            'name' => request()->params('name', $zone->name),
            'description' => request()->params('description', $zone->description),

            'sheet' => !$_REQUEST['data'] ? $zone->data : json_decode($_REQUEST['data']),
            'content' => !$_REQUEST['content'] ? $zone->content : json_decode($_REQUEST['content']),
        ];

        if(in_array(null, $data))
            return $this->jsonError("All fields are required");

        $zone->update($data);
        return $this->jsonSuccess("Zone updated successfully");
    }

    /**
     * Public Zone with enhanced search and filtering capabilities
     * 
     * @param string $code
     * @return void
     */
    public function public(string $code){
        $zone = Zone::where(DB::$capsule::raw("MD5(id)"), $code)->first();
        if(!$zone) return $this->jsonError("Zone not found", 404);

        // Handle backward compatibility - migrate old format to new flattened format
        $data = $this->ensureFlattenedFormat($zone);
        
        // Get query parameters
        $sheetName = $_REQUEST['sheet'] ?? null;
        $search = $_REQUEST['search'] ?? null;
        $column = $_REQUEST['column'] ?? null;
        $limit = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : null;
        $offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;
        $format = $_REQUEST['format'] ?? 'json'; // json, csv, summary
        $sortBy = $_REQUEST['sort_by'] ?? null;
        $sortOrder = $_REQUEST['sort_order'] ?? 'asc';
        $distinct = $_REQUEST['distinct'] ?? null;

        // If specific sheet requested
        if($sheetName){
            if(!isset($data[$sheetName])) {
                return $this->jsonError("Sheet '{$sheetName}' not found", 404);
            }
            
            $sheetData = $data[$sheetName];
            
            // TODO: Remove this part because it;s already handled while saving
                // only Left for backward compatibility
            // Remove last empty row if exists
            if (is_array($sheetData) && !empty($sheetData)) { 
                $lastRow = end($sheetData);
                if(empty(array_filter($lastRow))) {
                    array_pop($sheetData);
                }
            }
            
            $data = [$sheetName => $sheetData];
        }

        // Apply search filtering
        if($search && !empty($data)) {
            $data = $this->filterDataBySearch($data, $search, $column);
        }

        // Apply sorting
        if($sortBy && !empty($data)) {
            $data = $this->sortData($data, $sortBy, $sortOrder);
        }

        // Apply pagination
        if($limit && !empty($data)) {
            $data = $this->paginateData($data, $limit, $offset);
        }

        // Handle distinct values request
        if($distinct && !empty($data)) {
            return response()->json($this->getDistinctValues($data, $distinct));
        }

        // Format response based on requested format
        switch($format) {
            case 'csv':
                return $this->formatAsCSV($data, $sheetName);
            case 'summary':
                return response()->json($this->getDataSummary($data));
            case 'meta':                
                return response()->json([
                    'data' => $data,
                    'meta' => $this->getDataMeta($zone->content, $sheetName)
                ]);
            default:
                return $sheetName && isset($data[$sheetName]) ?
                    response()->json($data[$sheetName]) :
                    response()->json($data);
        }
    }

    /**
     * Ensure zone content is in the new flattened format
     * Migrates old nested format to new direct access format
     * 
     * @param Zone $zone
     * @return array
     */
    private function ensureFlattenedFormat(Zone $zone): array
    {
        $content = $zone->content;
        
        // Check if content is already in flattened format
        // Flattened format: direct key-value where keys are sheet names
        if($this->isFlattenedFormat($content)) {
            return $content;
        }
        
        // Convert old nested format to flattened format
        $flattened = [];
        
        if(is_array($content)) {
            foreach($content as $zoneContent) {
                if(is_array($zoneContent)) {
                    foreach($zoneContent as $sheetName => $sheetData) {
                        $flattened[$sheetName] = $sheetData;
                    }
                }
            }
        }
        
        // Save the migrated format back to the zone
        if(!empty($flattened) && $flattened !== $content) {
            $zone->update(['content' => $flattened]);
        }
        
        return $flattened;
    }

    /**
     * Check if content is in flattened format
     * 
     * @param mixed $content
     * @return bool
     */
    private function isFlattenedFormat($content): bool
    {
        if(!is_array($content) || empty($content)) {
            return true; // Empty or non-array is considered flattened
        }
        
        // Check if it's a simple associative array where values are arrays of rows
        // Old format would be: [['sheet1' => [...]], ['sheet2' => [...]]]
        // New format would be: ['sheet1' => [...], 'sheet2' => [...]]
        
        $firstKey = array_key_first($content);
        $firstValue = $content[$firstKey];
        
        // If first value is an array containing sheet names as keys, it's old format
        if(is_array($firstValue) && !empty($firstValue)) {
            $firstValueKey = array_key_first($firstValue);
            $firstValueValue = $firstValue[$firstValueKey];
            
            // If the structure is nested (array of arrays with sheet names), it's old format
            if(is_string($firstValueKey) && is_array($firstValueValue)) {
                return false; // Old nested format
            }
        }
        
        return true; // Assume flattened format
    }

    /**
     * Filter data by search term
     * 
     * @param array $data
     * @param string $search
     * @param string|null $column
     * @return array
     */
    private function filterDataBySearch(array $data, string $search, ?string $column = null): array
    {
        $filtered = [];
        
        foreach($data as $sheetName => $sheetData) {
            if(!is_array($sheetData) || empty($sheetData)) continue;
            
            $filteredRows = [];
            $headers = array_keys($sheetData[0] ?? []);
            
            foreach($sheetData as $row) {
                if(!is_array($row)) continue;
                
                $match = false;
                
                // Search in specific column if provided
                if($column && isset($row[$column])) {
                    $match = stripos((string)$row[$column], $search) !== false;
                } else {
                    // Search in all columns
                    foreach($row as $value) {
                        if(stripos((string)$value, $search) !== false) {
                            $match = true;
                            break;
                        }
                    }
                }
                
                if($match) {
                    $filteredRows[] = $row;
                }
            }
            
            if(!empty($filteredRows)) {
                $filtered[$sheetName] = $filteredRows;
            }
        }
        
        return $filtered;
    }

    /**
     * Sort data by column
     * 
     * @param array $data
     * @param string $sortBy
     * @param string $sortOrder
     * @return array
     */
    private function sortData(array $data, string $sortBy, string $sortOrder = 'asc'): array
    {
        foreach($data as $sheetName => &$sheetData) {
            if(!is_array($sheetData) || empty($sheetData)) continue;
            
            // Check if sort column exists
            if(!isset($sheetData[0][$sortBy])) continue;
            
            usort($sheetData, function($a, $b) use ($sortBy, $sortOrder) {
                $valueA = $a[$sortBy] ?? '';
                $valueB = $b[$sortBy] ?? '';
                
                // Try to compare as numbers if both are numeric
                if(is_numeric($valueA) && is_numeric($valueB)) {
                    $result = $valueA <=> $valueB;
                } else {
                    $result = strcasecmp((string)$valueA, (string)$valueB);
                }
                
                return $sortOrder === 'desc' ? -$result : $result;
            });
        }
        
        return $data;
    }

    /**
     * Paginate data
     * 
     * @param array $data
     * @param int $limit
     * @param int $offset
     * @return array
     */
    private function paginateData(array $data, int $limit, int $offset = 0): array
    {
        foreach($data as $sheetName => &$sheetData) {
            if(!is_array($sheetData)) continue;
            
            $sheetData = array_slice($sheetData, $offset, $limit);
        }
        
        return $data;
    }

    /**
     * Format data as CSV
     * 
     * @param array $data
     * @param string|null $sheetName
     * @return mixed
     */
    private function formatAsCSV(array $data, ?string $sheetName = null)
    {
        if(empty($data)) {
            return response('', 200, ['Content-Type' => 'text/csv']);
        }
        
        $output = '';
        
        foreach($data as $currentSheetName => $sheetData) {
            if(!is_array($sheetData) || empty($sheetData)) continue;
            
            // Add sheet header if multiple sheets
            if(!$sheetName && count($data) > 1) {
                $output .= "--- {$currentSheetName} ---\n";
            }
            
            // Add CSV headers
            $headers = array_keys($sheetData[0] ?? []);
            $output .= implode(',', array_map(function($header) {
                return '"' . str_replace('"', '""', $header) . '"';
            }, $headers)) . "\n";
            
            // Add data rows
            foreach($sheetData as $row) {
                $csvRow = array_map(function($value) {
                    return '"' . str_replace('"', '""', (string)$value) . '"';
                }, $row);
                $output .= implode(',', $csvRow) . "\n";
            }
            
            $output .= "\n";
        }
        
        $filename = ($sheetName ?: 'zone_data') . '_' . date('Y-m-d_H-i-s') . '.csv';

        // Use output buffering to avoid header issues and ensure clean output
        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        // Output BOM for UTF-8 compatibility (especially for Excel)
        echo "\xEF\xBB\xBF";
        echo $output;
        exit;
    }

    /**
     * Get data summary
     * 
     * @param array $data
     * @return array
     */
    private function getDataSummary(array $data): array
    {
        $summary = [
            'total_sheets' => count($data),
            'sheets' => []
        ];
        
        foreach($data as $sheetName => $sheetData) {
            if(!is_array($sheetData)) continue;
            
            $rowCount = count($sheetData);
            $columns = !empty($sheetData) ? array_keys($sheetData[0] ?? []) : [];
            
            $summary['sheets'][$sheetName] = [
                'row_count' => $rowCount,
                'column_count' => count($columns),
                'columns' => $columns,
                'sample_data' => array_slice($sheetData, 0, 3) // First 3 rows as sample
            ];
        }
        
        return $summary;
    }

    /**
     * Get metadata about the data
     * 
     * @param array $allContent
     * @param string|null $requestedSheet
     * @return array
     */
    private function getDataMeta(array $allContent, ?string $requestedSheet = null): array
    {
        $meta = [
            'available_sheets' => [],
            'total_sheets' => 0,
            'requested_sheet' => $requestedSheet
        ];
        
        // Handle both old and new formats
        if($this->isFlattenedFormat($allContent)) {
            // New flattened format: direct access
            $meta['available_sheets'] = array_keys($allContent);
        } else {
            // Old nested format: loop through nested structure
            foreach($allContent as $zoneContent) {
                if(!is_array($zoneContent)) continue;
                
                foreach($zoneContent as $sheetName => $sheetData) {
                    if(!in_array($sheetName, $meta['available_sheets'])) {
                        $meta['available_sheets'][] = $sheetName;
                    }
                }
            }
        }
        
        $meta['total_sheets'] = count($meta['available_sheets']);
        
        return $meta;
    }

    /**
     * Get distinct values from a specific column across all sheets
     * 
     * @param array $data
     * @param string $columnName
     * @return array
     */
    private function getDistinctValues(array $data, string $columnName): array
    {
        $distinctValues = [];
        $result = [];
        
        foreach($data as $sheetName => $sheetData) {
            if(!is_array($sheetData) || empty($sheetData)) continue;
            
            $sheetDistinct = [];
            
            foreach($sheetData as $row) {
                if(!is_array($row) || !isset($row[$columnName])) continue;
                
                $value = $row[$columnName];
                
                // Skip null, empty, or already seen values
                if($value === null || $value === '') continue;
                
                // Convert to string for comparison to handle different data types
                $valueKey = (string)$value;
                
                if(!isset($distinctValues[$valueKey])) {
                    $distinctValues[$valueKey] = $value;
                    $sheetDistinct[] = $value;
                }
            }
            
            // Only include sheets that have values for this column
            if(!empty($sheetDistinct)) {
                $result[$sheetName] = array_values(array_unique($sheetDistinct, SORT_REGULAR));
            }
        }
        
        // If only one sheet or user wants combined results, return flattened array
        if(count($result) === 1) {
            return [
                'column' => $columnName,
                'values' => array_values($distinctValues),
                'count' => count($distinctValues),
                'sheet' => array_key_first($result)
            ];
        }
        
        // Multiple sheets - return organized by sheet
        return [
            'column' => $columnName,
            'sheets' => $result,
            'all_values' => array_values($distinctValues),
            'total_count' => count($distinctValues)
        ];
    }

    /**
     * Get zone public code (MD5 hash of ID)
     * 
     * @param int $id
     * @return void
     */
    public function getCode(int $id)
    {
        $zone = Zone::find($id);
        if(!$zone) return $this->jsonError("Zone not found", 404);

        return response()->json([
            'code' => md5($zone->id),
            'zone_id' => $zone->id,
            'name' => $zone->name
        ]);
    }

    public static function routes()
    {
        app()::get('', ['name'=>'zones.list', 'ZonesController@index']);
        app()::get('show/{id}', ['name'=>'zones.show', 'ZonesController@show']);
        app()::get('delete/{id}', ['name'=>'zones.delete', 'ZonesController@delete']);
        app()::get('code/{id}', ['name'=>'zones.code', 'ZonesController@getCode']);

        app()::post('create', ['name'=>'zones.store', 'ZonesController@store']);
        app()::post('update/{id}', ['name'=>'zones.update', 'ZonesController@update']);
    }
}