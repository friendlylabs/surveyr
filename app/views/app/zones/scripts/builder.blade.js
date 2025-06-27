let zoneBuilder = {
    rule: {
        zone: '',
        sheet: '',
        format: 'default',
        search: '',
        column: '',
        sort_by: '',
        sort_order: 'asc',
        limit: '',
        distinct: '',
        output_name: '',
        availableSheets: [],
        availableColumns: []
    },
    availableZones: @json($zones->map(function($zone) { return ['id' => $zone->id, 'name' => $zone->name]; })),
    
    init() {
        this.renderBuilder();
        this.bindEvents();
    },
    
    renderBuilder() {
        const builderHTML = `
            <div class="zone-builder">
                
                <div id="ruleContainer">
                    ${this.renderRule()}
                </div>
                
                <div id="previewContainer" class="mt-4" style="display: none;">
                    <h6 class="text-muted">Preview</h6>
                    <div class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                        <pre id="previewContent"></pre>
                    </div>
                </div>
                
                <div id="urlContainer" class="mt-4" style="display: none;">
                    <h6 class="text-muted">Generated URL</h6>
                    <div class="bg-light p-3 rounded">
                        <div id="urlResult"></div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('zoneBuilderBlock').innerHTML = builderHTML;
    },
    
    renderRule() {
        return `
            <div class="rule-item mb-3">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Zone</label>
                        <select class="form-select form-select-sm" onchange="zoneBuilder.onZoneChange(this.value)">
                            <option value="">Select Zone</option>
                            ${this.availableZones.map(zone => 
                                `<option value="${zone.id}" ${this.rule.zone == zone.id ? 'selected' : ''}>${zone.name}</option>`
                            ).join('')}
                        </select>
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label small">Sheet</label>
                        <select class="form-select form-select-sm" id="sheetSelect" onchange="zoneBuilder.onSheetChange(this.value)" ${!this.rule.zone ? 'disabled' : ''}>
                            <option value="">All Sheets</option>
                            ${this.rule.availableSheets ? this.rule.availableSheets.map(sheet => 
                                `<option value="${sheet}" ${this.rule.sheet === sheet ? 'selected' : ''}>${sheet}</option>`
                            ).join('') : ''}
                        </select>
                        ${!this.rule.zone ? '<small class="text-muted">Select a zone first</small>' : ''}
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label small">Format</label>
                        <select class="form-select form-select-sm" onchange="zoneBuilder.updateRule('format', this.value)">
                            <option value="default" ${this.rule.format === 'default' ? 'selected' : ''}>Default</option>
                            <option value="json" ${this.rule.format === 'json' ? 'selected' : ''}>JSON</option>
                            <option value="csv" ${this.rule.format === 'csv' ? 'selected' : ''}>CSV</option>
                            <option value="summary" ${this.rule.format === 'summary' ? 'selected' : ''}>Summary</option>
                        </select>
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label small">Search</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Search term (optional)" 
                                value="${this.rule.search || ''}" onchange="zoneBuilder.updateRule('search', this.value)">
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label small">Column Filter</label>
                        <select class="form-select form-select-sm" id="columnSelect" onchange="zoneBuilder.updateRule('column', this.value)" ${!this.rule.zone ? 'disabled' : ''}>
                            <option value="">Any Column</option>
                            ${this.rule.availableColumns ? this.rule.availableColumns.map(column => 
                                `<option value="${column}" ${this.rule.column === column ? 'selected' : ''}>${column}</option>`
                            ).join('') : ''}
                        </select>
                    </div>
                    
                    <div class="col-4">
                        <label class="form-label small">Sort By</label>
                        <select class="form-select form-select-sm" id="sortSelect" onchange="zoneBuilder.updateRule('sort_by', this.value)" ${!this.rule.zone ? 'disabled' : ''}>
                            <option value="">No Sorting</option>
                            ${this.rule.availableColumns ? this.rule.availableColumns.map(column => 
                                `<option value="${column}" ${this.rule.sort_by === column ? 'selected' : ''}>${column}</option>`
                            ).join('') : ''}
                        </select>
                    </div>
                    
                    <div class="col-4">
                        <label class="form-label small">Order</label>
                        <select class="form-select form-select-sm" onchange="zoneBuilder.updateRule('sort_order', this.value)">
                            <option value="asc" ${this.rule.sort_order === 'asc' ? 'selected' : ''}>Ascending</option>
                            <option value="desc" ${this.rule.sort_order === 'desc' ? 'selected' : ''}>Descending</option>
                        </select>
                    </div>
                    
                    <div class="col-4">
                        <label class="form-label small">Limit</label>
                        <input type="number" class="form-control form-control-sm" placeholder="Rows" min="1"
                                value="${this.rule.limit || ''}" onchange="zoneBuilder.updateRule('limit', this.value)">
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label small">Distinct Column</label>
                        <select class="form-select form-select-sm" id="distinctSelect" onchange="zoneBuilder.updateRule('distinct', this.value)" ${!this.rule.zone ? 'disabled' : ''}>
                            <option value="">No Distinct Filter</option>
                            ${this.rule.availableColumns ? this.rule.availableColumns.map(column => 
                                `<option value="${column}" ${this.rule.distinct === column ? 'selected' : ''}>${column}</option>`
                            ).join('') : ''}
                        </select>
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label small">Output Name</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Custom filename" 
                                value="${this.rule.output_name || ''}" onchange="zoneBuilder.updateRule('output_name', this.value)">
                    </div>
                </div>
            </div>
        `;
    },
    
    updateRule(field, value) {
        this.rule[field] = value;
        this.updateButtonStates();
    },
    
    updateButtonStates() {
        const generateBtn = document.getElementById('generateUrlBtn');
        const previewBtn = document.getElementById('previewDataBtn');
        
        if (generateBtn) {
            generateBtn.disabled = !this.rule.zone;
        }
        if (previewBtn) {
            previewBtn.disabled = !this.rule.zone;
        }
    },
    
    async onZoneChange(zoneId) {
        this.updateRule('zone', zoneId);
        this.updateRule('sheet', ''); // Reset sheet selection
        
        // Hide both preview and URL containers when zone changes
        document.getElementById('previewContainer').style.display = 'none';
        document.getElementById('urlContainer').style.display = 'none';
        
        if (zoneId) {
            try {
                // Show loading state
                const sheetSelect = document.getElementById('sheetSelect');
                if (sheetSelect) {
                    sheetSelect.innerHTML = '<option value="">Loading sheets...</option>';
                    sheetSelect.disabled = true;
                }
                
                const sheets = await this.fetchZoneSheets(zoneId);
                this.rule.availableSheets = sheets;
                
                // Update the sheet dropdown
                if (sheetSelect) {
                    sheetSelect.innerHTML = '<option value="">All Sheets</option>' + 
                        sheets.map(sheet => `<option value="${sheet}">${sheet}</option>`).join('');
                    sheetSelect.disabled = false;
                }
            } catch (error) {
                console.error('Failed to fetch sheets:', error);
                const sheetSelect = document.getElementById('sheetSelect');
                if (sheetSelect) {
                    sheetSelect.innerHTML = '<option value="">Failed to load sheets</option>';
                    sheetSelect.disabled = true;
                }
            }
        } else {
            this.rule.availableSheets = [];
            const sheetSelect = document.getElementById('sheetSelect');
            if (sheetSelect) {
                sheetSelect.innerHTML = '<option value="">Select a zone first</option>';
                sheetSelect.disabled = true;
            }
        }
        
        this.updateButtonStates();
    },
    
    async onSheetChange(sheetName) {
        this.updateRule('sheet', sheetName);
        this.updateRule('column', ''); // Reset column selections
        this.updateRule('sort_by', '');
        this.updateRule('distinct', '');
        
        // Hide both preview and URL containers when sheet changes
        document.getElementById('previewContainer').style.display = 'none';
        document.getElementById('urlContainer').style.display = 'none';
        
        if (sheetName && this.rule.zone) {
            try {
                // Show loading state for column dropdowns
                ['columnSelect', 'sortSelect', 'distinctSelect'].forEach(id => {
                    const select = document.getElementById(id);
                    if (select) {
                        select.innerHTML = '<option value="">Loading columns...</option>';
                        select.disabled = true;
                    }
                });
                
                const columns = await this.fetchSheetColumns(this.rule.zone, sheetName);
                this.rule.availableColumns = columns;
                
                // Update column dropdowns
                this.updateColumnDropdowns(columns);
            } catch (error) {
                console.error('Failed to fetch columns:', error);
                this.updateColumnDropdowns([], true);
            }
        } else {
            this.rule.availableColumns = [];
            this.updateColumnDropdowns([]);
        }
    },
    
    updateColumnDropdowns(columns, error = false) {
        const dropdowns = [
            { id: 'columnSelect', defaultText: 'Any Column' },
            { id: 'sortSelect', defaultText: 'No Sorting' },
            { id: 'distinctSelect', defaultText: 'No Distinct Filter' }
        ];
        
        dropdowns.forEach(dropdown => {
            const select = document.getElementById(dropdown.id);
            if (select) {
                if (error) {
                    select.innerHTML = '<option value="">Failed to load columns</option>';
                    select.disabled = true;
                } else if (columns.length === 0) {
                    select.innerHTML = '<option value="">' + dropdown.defaultText + '</option>';
                    select.disabled = !this.rule.zone;
                } else {
                    select.innerHTML = '<option value="">' + dropdown.defaultText + '</option>' + 
                        columns.map(column => '<option value="' + column + '">' + column + '</option>').join('');
                    select.disabled = false;
                }
            }
        });
    },
    
    async fetchZoneSheets(zoneId) {
        try {
            const zoneCode = await this.getZoneCode(zoneId);
            const response = await fetch('/api/zones/show/' + zoneCode + '?format=meta');
            
            if (!response.ok) throw new Error('Failed to fetch zone metadata');
            
            const data = await response.json();
            return data.meta?.available_sheets || [];
        } catch (error) {
            console.error('Error fetching zone sheets:', error);
            return [];
        }
    },
    
    async fetchSheetColumns(zoneId, sheetName) {
        try {
            const zoneCode = await this.getZoneCode(zoneId);
            const response = await fetch('/api/zones/show/' + zoneCode + '?sheet=' + encodeURIComponent(sheetName) + '&limit=1');
            
            if (!response.ok) throw new Error('Failed to fetch sheet data');
            
            const data = await response.json();
            
            // Extract column names from the first row
            if (Array.isArray(data) && data.length > 0 && typeof data[0] === 'object') {
                return Object.keys(data[0]);
            }
            
            return [];
        } catch (error) {
            console.error('Error fetching sheet columns:', error);
            return [];
        }
    },
    
    async previewData() {
        if (!this.rule.zone) return;
        
        try {
            const result = await this.executeRule(true);
            document.getElementById('previewContent').textContent = JSON.stringify(result, null, 2);
            
            // Show preview and hide URL container
            document.getElementById('previewContainer').style.display = 'block';
            document.getElementById('urlContainer').style.display = 'none';
        } catch (error) {
            toast.error({ message: 'Failed to generate preview: ' + error.message });
        }
    },
    
    async generateUrl() {
        if (!this.rule.zone) return;
        
        try {
            const urlInfo = await this.buildUrl();
            this.displayUrl(urlInfo);
            
            // Show URL container and hide preview
            document.getElementById('urlContainer').style.display = 'block';
            document.getElementById('previewContainer').style.display = 'none';
            
            toast.success({ message: 'Data URL generated successfully!' });
        } catch (error) {
            toast.error({ message: 'Failed to generate URL: ' + error.message });
        }
    },
    
    async buildUrl() {
        const zoneCode = await this.getZoneCode(this.rule.zone);
        const url = this.buildApiUrl(zoneCode, this.rule);
        const fullUrl = window.location.origin + url;
        
        return {
            name: this.rule.output_name || 'Data Access',
            url: fullUrl,
            relativeUrl: url,
            rule: this.rule,
            zone_code: zoneCode,
            generated_at: new Date().toISOString()
        };
    },
    
    displayUrl(urlInfo) {
        const urlResult = document.getElementById('urlResult');
        
        let html = `
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">${urlInfo.name}</h6>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary btn-sm" onclick="zoneBuilder.copyUrl('${urlInfo.url}')">
                                <i class="fa-solid fa-copy"></i> Copy
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="window.open('${urlInfo.url}', '_blank')">
                                <i class="fa-solid fa-external-link-alt"></i> Open
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Zone Code:</small> 
                        <code class="small">${urlInfo.zone_code}</code>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">URL:</small>
                        <div class="form-control form-control-sm font-monospace small" style="font-size: 0.7rem; word-break: break-all;">
                            ${urlInfo.url}
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Parameters:</small>
                        <div class="mt-1">
                            ${this.formatRuleParameters(urlInfo.rule)}
                        </div>
                    </div>
                    
                    <div class="mb-0">
                        <small class="text-muted">Generated:</small> 
                        <small class="text-body-secondary">${new Date(urlInfo.generated_at).toLocaleString()}</small>
                    </div>
                </div>
            </div>
            
            <div class="mt-3 d-grid gap-2">
                <button class="btn btn-outline-primary btn-sm" onclick="zoneBuilder.exportUrl(${JSON.stringify(urlInfo).replace(/"/g, '&quot;')})">
                    <i class="fa-solid fa-download me-2"></i>Export URL Info
                </button>
            </div>
        `;
        
        urlResult.innerHTML = html;
    },
    
    formatRuleParameters(rule) {
        const params = [];
        if (rule.sheet) params.push(`<span class="badge bg-light text-dark">sheet: ${rule.sheet}</span>`);
        if (rule.format && rule.format !== 'json') params.push(`<span class="badge bg-info">format: ${rule.format}</span>`);
        if (rule.search) params.push(`<span class="badge bg-warning">search: ${rule.search}</span>`);
        if (rule.column) params.push(`<span class="badge bg-secondary">column: ${rule.column}</span>`);
        if (rule.sort_by) params.push(`<span class="badge bg-primary">sort: ${rule.sort_by} ${rule.sort_order}</span>`);
        if (rule.limit) params.push(`<span class="badge bg-success">limit: ${rule.limit}</span>`);
        if (rule.distinct) params.push(`<span class="badge bg-danger">distinct: ${rule.distinct}</span>`);
        
        return params.length > 0 ? params.join(' ') : '<span class="text-muted small">No parameters</span>';
    },
    
    copyUrl(url) {
        navigator.clipboard.writeText(url).then(() => {
            toast.success({ message: 'URL copied to clipboard!' });
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            toast.success({ message: 'URL copied to clipboard!' });
        });
    },
    
    exportUrl(urlInfo) {
        // Create a formatted text export
        let exportText = 'Data Zone URL Export\n';
        exportText += '='.repeat(50) + '\n';
        exportText += 'Generated: ' + new Date(urlInfo.generated_at).toLocaleString() + '\n\n';
        
        exportText += `Name: ${urlInfo.name}\n`;
        exportText += `URL: ${urlInfo.url}\n`;
        exportText += `Zone Code: ${urlInfo.zone_code}\n`;
        if (urlInfo.rule.sheet) exportText += `Sheet: ${urlInfo.rule.sheet}\n`;
        if (urlInfo.rule.format !== 'json') exportText += `Format: ${urlInfo.rule.format}\n`;
        if (urlInfo.rule.search) exportText += `Search: ${urlInfo.rule.search}\n`;
        if (urlInfo.rule.column) exportText += `Column: ${urlInfo.rule.column}\n`;
        if (urlInfo.rule.sort_by) exportText += `Sort: ${urlInfo.rule.sort_by} (${urlInfo.rule.sort_order})\n`;
        if (urlInfo.rule.limit) exportText += `Limit: ${urlInfo.rule.limit}\n`;
        if (urlInfo.rule.distinct) exportText += `Distinct: ${urlInfo.rule.distinct}\n`;
        
        // Download as text file
        const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
        const filename = 'zone_url_' + timestamp + '.txt';
        
        const blob = new Blob([exportText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    },
    
    async executeRule(preview = false) {
        if (!this.rule.zone) throw new Error('No zone selected');
        
        try {
            const zoneCode = await this.getZoneCode(this.rule.zone);
            const url = this.buildApiUrl(zoneCode, this.rule);
            
            const response = await fetch(url);
            if (!response.ok) throw new Error('HTTP ' + response.status);
            
            let data;
            if (this.rule.format === 'csv') {
                data = await response.text();
            } else {
                data = await response.json();
            }
            
            return {
                generated_at: new Date().toISOString(),
                rule: this.rule,
                result: preview && this.rule.format === 'json' ? 
                    (Array.isArray(data) ? data.slice(0, 3) : data) : data
            };
        } catch (error) {
            throw new Error('Failed to execute rule: ' + error.message);
        }
    },
    
    buildApiUrl(zoneCode, rule) {
        const baseUrl = '/api/zones/show/' + zoneCode;
        const params = new URLSearchParams();
        
        if (rule.sheet) params.append('sheet', rule.sheet);
        if (rule.format) params.append('format', rule.format);
        if (rule.search) params.append('search', rule.search);
        if (rule.column) params.append('column', rule.column);
        if (rule.sort_by) params.append('sort_by', rule.sort_by);
        if (rule.sort_order) params.append('sort_order', rule.sort_order);
        if (rule.limit) params.append('limit', rule.limit);
        if (rule.distinct) params.append('distinct', rule.distinct);
        
        return baseUrl + (params.toString() ? '?' + params.toString() : '');
    },
    
    async getZoneCode(zoneId) {
        // Get the MD5 hash code for the zone
        const response = await fetch('{{ route('zones.list') }}/code/' + zoneId );
        if (!response.ok) throw new Error('Failed to get zone code');
        const data = await response.json();
        return data.code;
    },
    
    bindEvents() {
        // Add keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') {
                this.generateUrl();
            }
        });
    }
};

// Initialize when offcanvas is shown
document.getElementById('datazoneBuilderOffCanvas').addEventListener('shown.bs.offcanvas', function () {
    zoneBuilder.init();
});