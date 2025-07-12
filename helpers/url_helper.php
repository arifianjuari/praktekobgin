<?php
/**
 * URL Helper - Fungsi bantuan untuk menangani URL di aplikasi
 * Memastikan URL konsisten di development dan production
 */

if (!function_exists('site_url')) {
    /**
     * Menghasilkan URL yang lengkap dengan base URL
     * 
     * @param string $path - Path relatif untuk ditambahkan ke base URL
     * @return string - URL lengkap
     */
    function site_url($path = '')
    {
        // Pastikan path dimulai dengan / jika tidak kosong
        if (!empty($path) && $path[0] !== '/') {
            $path = '/' . $path;
        }
        
        return BASE_URL . $path;
    }
}

if (!function_exists('asset_url')) {
    /**
     * Menghasilkan URL untuk asset (css, js, img)
     * 
     * @param string $path - Path relatif asset
     * @return string - URL lengkap ke asset
     */
    function asset_url($path = '')
    {
        if (!empty($path) && $path[0] !== '/') {
            $path = '/' . $path;
        }
        
        return BASE_URL . '/assets' . $path;
    }
}

if (!function_exists('module_url')) {
    /**
     * Menghasilkan URL untuk modul tertentu dengan action
     * 
     * @param string $module - Nama modul
     * @param string $action - Nama action
     * @param array $params - Parameter tambahan (opsional)
     * @return string - URL lengkap modul dengan action
     */
    function module_url($module, $action = '', $params = [])
    {
        $url = BASE_URL . '/index.php?module=' . urlencode($module);
        
        if (!empty($action)) {
            $url .= '&action=' . urlencode($action);
        }
        
        if (!empty($params) && is_array($params)) {
            foreach ($params as $key => $value) {
                $url .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }
        
        return $url;
    }
}

// Debug function to log URL information
function debug_url_info($message = 'URL Info')
{
    error_log($message . ': BASE_URL = ' . BASE_URL);
    error_log($message . ': REQUEST_URI = ' . $_SERVER['REQUEST_URI']);
    error_log($message . ': SCRIPT_NAME = ' . $_SERVER['SCRIPT_NAME']);
    error_log($message . ': PATH_INFO = ' . ($_SERVER['PATH_INFO'] ?? 'Not set'));
    error_log($message . ': Host = ' . $_SERVER['HTTP_HOST']);
}
