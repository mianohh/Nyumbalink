<?php
/**
 * UI/UX Enhancement System
 * Modern component library, DataTables integration, responsive design
 */

class ComponentLibrary {
    public static function header(array $options = []): string {
        $pageTitle = $options['pageTitle'] ?? APP_NAME;
        $showMenu = $options['showMenu'] ?? true;
        $showFlash = $options['showFlash'] ?? true;
        $customClass = $options['class'] ?? '';
        
        ob_start();
        require __DIR__ . '/views/header.php';
        return ob_get_clean();
    }
    
    public static function footer(array $options = []): string {
        $customClass = $options['class'] ?? '';
        
        ob_start();
        require __DIR__ . '/views/footer.php';
        return ob_get_clean();
    }
    
    public static function card(array $options): string {
        $title = $options['title'] ?? '';
        $content = $options['content'] ?? '';
        $header = $options['header'] ?? '';
        $footer = $options['footer'] ?? '';
        $class = $options['class'] ?? 'card';
        $headerClass = $options['headerClass'] ?? 'card-header';
        $bodyClass = $options['bodyClass'] ?? 'card-body';
        $footerClass = $options['footerClass'] ?? 'card-footer';
        
        $html = '<div class="' . $class . '">';
        
        if ($header) {
            $html .= '<div class="' . $headerClass . '">';
            $html .= '<h5 class="mb-0">' . $header . '</h5>';
            $html .= '</div>';
        }
        
        $html .= '<div class="' . $bodyClass . '">';
        $html .= $content;
        $html .= '</div>';
        
        if ($footer) {
            $html .= '<div class="' . $footerClass . '">';
            $html .= $footer;
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    public static function table(array $options): string {
        $headers = $options['headers'] ?? [];
        $rows = $options['rows'] ?? [];
        $id = $options['id'] ?? 'dataTable';
        $class = $options['class'] ?? 'table table-striped table-hover';
        $searchable = $options['searchable'] ?? true;
        $sortable = $options['sortable'] ?? true;
        $pagination = $options['pagination'] ?? true;
        $export = $options['export'] ?? false;
        
        ob_start();
        require __DIR__ . '/views/table.php';
        return ob_get_clean();
    }
}

class DataTable {
    private $query;
    private $columns;
    private $options;
    
    public function __construct(array $options) {
        $this->options = array_merge([
            'paging' => true,
            'lengthMenu' => [[10, 25, 50, -1], [10, 25, 50, 'All']],
            'order' => [[0, 'asc']],
            'dom' => 'Blfrtip',
            'buttons' => ['copy', 'excel', 'pdf', 'print']
        ], $options);
        
        $this->columns = $this->defineColumns($options['columns'] ?? []);
        $this->buildQuery($options['query'] ?? '');
    }
    
    private function defineColumns(array $columns): array {
        $definedColumns = [];
        foreach ($columns as $index => $config) {
            if (is_array($config)) {
                $definedColumns[] = [
                    'data' => $config['data'],
                    'name' => $config['name'] ?? $config['data'],
                    'title' => $config['title'] ?? $config['name'] ?? '',
                    'orderable' => $config['orderable'] ?? true,
                    'searchable' => $config['searchable'] ?? true,
                    'className' => $config['className'] ?? ''
                ];
            } else {
                $definedColumns[] = [
                    'data' => $config,
                    'name' => $config,
                    'title' => $config,
                    'orderable' => true,
                    'searchable' => true,
                    'className' => ''
                ];
            }
        }
        return $definedColumns;
    }
    
    private function buildQuery(string $query): void {
        $this->query = $query;
    }
    
    public function render(array $data): string {
        ob_start();
        require __DIR__ . '/views/data_table.php';
        return ob_get_clean();
    }
}

class ResponsiveDesign {
    public static function viewport(): string {
        $css = "";
        $css .= '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
        $css .= '<style>';
        $css .= ':root { --primary-color: #0489ef; --secondary-color: #6c757d; --success-color: #28a745; --danger-color: #dc3545; --warning-color: #ffc107; }';
        $css .= '.container { width: 100%; max-width: 1200px; margin: 0 auto; }';
        $css .= '@media (max-width: 768px) { .container { padding: 0 15px; } }';
        $css .= '.row { display: flex; flex-wrap: wrap; margin: 0 -15px; }';
        $css .= '.col { flex: 1 0 0%; padding: 0 15px; }';
        $css .= '@media (min-width: 576px) { .col { max-width: 540px; } }';
        $css .= '@media (min-width: 768px) { .col { max-width: 720px; } }';
        $css .= '@media (min-width: 992px) { .col { max-width: 960px; } }';
        $css .= '@media (min-width: 1200px) { .col { max-width: 1140px; } }';
        $css .= '.d-flex { display: flex; }';
        $css .= '.justify-content-between { justify-content: space-between; }';
        $css .= '.align-items-center { align-items: center; }';
        $css .= '.mb-4 { margin-bottom: 1.5rem; }';
        $css .= '@media (max-width: 576px) { .mb-4 { margin-bottom: 1rem; } }';
        $css .= '.card { border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); }';
        $css .= '.btn { padding: 0.375rem 0.75rem; border-radius: 0.375rem; }';
        $css .= '@media (max-width: 576px) { .btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; } }';
        $css .= '.table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }';
        $css .= '@media (max-width: 768px) { .table-responsive { margin: 0 -15px; } }';
        $css .= '@media (max-width: 768px) { .table th, .table td { padding: 0.5rem; font-size: 0.875rem; } }';
        $css .= 'body { font-size: 16px; }';
        $css .= '@media (max-width: 576px) { body { font-size: 14px; } }';
        $css .= '</style>';
        return $css;
    }
    
    public static function darkMode(array $options = []): string {
        $enabled = $options['enabled'] ?? false;
        $prefersDark = $options['prefersDark'] ?? true;
        
        if (!$enabled && !$prefersDark) {
            return '';
        }
        
        $css = '<style>';
        $css .= '@media (prefers-color-scheme: dark) {';
        $css .= '  body { background-color: #1a1a1a; color: #e0e0e0; }';
        $css .= '  .card { background-color: #2d2d2d; border-color: #404040; }';
        $css .= '  .card-header { background-color: #333333; border-color: #404040; }';
        $css .= '  .card-body { background-color: #2d2d2d; }';
        $css .= '  .modal-content { background-color: #2d2d2d; border-color: #404040; }';
        $css .= '  .form-control { background-color: #333333; border-color: #555555; color: #e0e0e0; }';
        $css .= '  .form-control:focus { background-color: #333333; border-color: #0489ef; color: #e0e0e0; }';
        $css .= '  .table { color: #e0e0e0; }';
        $css .= '  .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(255,255,255,0.05); }';
        $css .= '  .text-muted { color: #a0a0a0 !important; }';
        $css .= '}';
        $css .= '</style>';
        
        return $css;
    }
}

class Animation {
    public static function microInteractions(): string {
        $css = '<style>';
        $css .= '.btn { transition: all 0.3s ease; }';
        $css .= '.btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }';
        $css .= '.card { transition: transform 0.3s ease, box-shadow 0.3s ease; }';
        $css .= '.card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }';
        $css .= '.menu-toggle { transition: transform 0.3s ease; }';
        $css .= '.menu-toggle.toggled { transform: rotate(90deg); }';
        $css .= '.alert { animation: fadeIn 0.5s ease; }';
        $css .= '@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }';
        $css .= '</style>';
        return $css;
    }
    
    public static function pageTransitions(): string {
        $css = '<style>';
        $css .= 'main { animation: fadeInUp 0.5s ease; }';
        $css .= '@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }';
        $css .= '</style>';
        return $css;
    }
}

class Accessibility {
    public static function wcagCompliance(array $options = []): string {
        $ariaLabels = $options['ariaLabels'] ?? true;
        $keyboardNavigation = $options['keyboardNavigation'] ?? true;
        $colorContrast = $options['colorContrast'] ?? true;
        
        $css = '<style>';
        $css .= '.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }';
        $css .= '.btn:focus, .form-control:focus { outline: 2px solid #0489ef; outline-offset: 2px; }';
        $css .= '.alert-dismissible .btn-close { font-size: 1.2em; }';
        $css .= '.modal:focus-within { outline: none; }';
        $css .= '@media (prefers-reduced-motion: reduce) {';
        $css .= '  *, *::before, *::after {';
        $css .= '    animation-duration: 0.01ms !important;';
        $css .= '    animation-iteration-count: 1 !important;';
        $css .= '    transition-duration: 0.01ms !important;';
        $css .= '  }';
        $css .= '}';
        $css .= '</style>';
        return $css;
    }
}