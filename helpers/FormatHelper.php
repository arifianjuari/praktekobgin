<?php
/**
 * Format Helper
 * Contains utility functions for formatting data
 * 
 * @author Your Name
 * @version 1.0
 */

class FormatHelper 
{
    /**
     * Format number to Indonesian Rupiah currency
     */
    public static function formatRupiah($amount) 
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    
    /**
     * Format date to Indonesian format
     */
    public static function formatDateIndonesian($date, $format = 'd F Y') 
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $timestamp = is_string($date) ? strtotime($date) : $date;
        $formatted = date($format, $timestamp);
        
        // Replace English month names with Indonesian
        foreach ($months as $num => $month) {
            $formatted = str_replace(date('F', mktime(0, 0, 0, $num, 1)), $month, $formatted);
        }
        
        return $formatted;
    }
    
    /**
     * Truncate text with ellipsis
     */
    public static function truncateText($text, $length = 100, $suffix = '...') 
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return mb_strimwidth($text, 0, $length, $suffix);
    }
    
    /**
     * Clean and sanitize HTML output
     */
    public static function cleanOutput($text) 
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Format duration in minutes to human readable format
     */
    public static function formatDuration($minutes) 
    {
        if ($minutes < 60) {
            return $minutes . ' menit';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes == 0) {
            return $hours . ' jam';
        }
        
        return $hours . ' jam ' . $remainingMinutes . ' menit';
    }
    
    /**
     * Generate slug from text
     */
    public static function generateSlug($text) 
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }
    
    /**
     * Format file size
     */
    public static function formatFileSize($bytes) 
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Format phone number to Indonesian format
     */
    public static function formatPhoneNumber($phone) 
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to Indonesian format
        if (substr($phone, 0, 1) == '0') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) != '62') {
            $phone = '62' . $phone;
        }
        
        return '+' . $phone;
    }
    
    /**
     * Convert newlines to HTML breaks
     */
    public static function nl2br($text) 
    {
        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }
    
    /**
     * Generate random string
     */
    public static function generateRandomString($length = 10) 
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
}
