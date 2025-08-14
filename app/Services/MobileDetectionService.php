<?php

namespace App\Services;

use Illuminate\Http\Request;

class MobileDetectionService
{
    /**
     * Detect if the current request is from a mobile device
     */
    public function isMobile(Request $request): bool
    {
        $userAgent = $request->userAgent();
        
        if (!$userAgent) {
            return false;
        }

        // Common mobile device patterns
        $mobilePatterns = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone',
            'BlackBerry', 'Opera Mini', 'IEMobile', 'Mobile Safari'
        ];

        foreach ($mobilePatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if the current request is from a tablet device
     */
    public function isTablet(Request $request): bool
    {
        $userAgent = $request->userAgent();
        
        if (!$userAgent) {
            return false;
        }

        // Tablet-specific patterns
        $tabletPatterns = [
            'iPad', 'Tablet', 'PlayBook'
        ];

        // Check for explicit tablet patterns
        foreach ($tabletPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        // Check for Android tablets (common pattern: SM-T series)
        if (stripos($userAgent, 'Android') !== false && 
            (preg_match('/SM-T\d+/', $userAgent) || 
             stripos($userAgent, 'Android.*Tablet') !== false)) {
            return true;
        }

        return false;
    }

    /**
     * Detect if the current request is from a mobile or tablet device
     */
    public function isMobileOrTablet(Request $request): bool
    {
        return $this->isMobile($request) || $this->isTablet($request);
    }

    /**
     * Get device type for logging or analytics
     */
    public function getDeviceType(Request $request): string
    {
        if ($this->isTablet($request)) {
            return 'tablet';
        }
        
        if ($this->isMobile($request)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
}
