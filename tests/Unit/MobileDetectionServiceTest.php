<?php

namespace Tests\Unit;

use App\Services\MobileDetectionService;
use Illuminate\Http\Request;
use Tests\TestCase;

class MobileDetectionServiceTest extends TestCase
{
    private MobileDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MobileDetectionService();
    }

    public function test_detects_mobile_devices()
    {
        $mobileUserAgents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36',
            'Mozilla/5.0 (Windows Phone 10.0; Android 6.0.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Mobile Safari/537.36',
            'Mozilla/5.0 (BlackBerry; U; BlackBerry 9900; en) AppleWebKit/534.11+ (KHTML, like Gecko) Version/7.1.0.346 Mobile Safari/534.11+',
        ];

        foreach ($mobileUserAgents as $userAgent) {
            $request = Request::create('/');
            $request->headers->set('User-Agent', $userAgent);
            
            $this->assertTrue($this->service->isMobile($request), "Failed to detect mobile: $userAgent");
        }
    }

    public function test_detects_tablet_devices()
    {
        $tabletUserAgents = [
            'Mozilla/5.0 (iPad; CPU OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 11; SM-T870) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Safari/537.36',
        ];

        foreach ($tabletUserAgents as $userAgent) {
            $request = Request::create('/');
            $request->headers->set('User-Agent', $userAgent);
            
            $this->assertTrue($this->service->isTablet($request), "Failed to detect tablet: $userAgent");
        }
    }

    public function test_detects_desktop_devices()
    {
        $desktopUserAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Safari/537.36',
        ];

        foreach ($desktopUserAgents as $userAgent) {
            $request = Request::create('/');
            $request->headers->set('User-Agent', $userAgent);
            
            $this->assertFalse($this->service->isMobile($request), "Incorrectly detected mobile: $userAgent");
            $this->assertFalse($this->service->isTablet($request), "Incorrectly detected tablet: $userAgent");
        }
    }

    public function test_device_type_detection()
    {
        // Test mobile
        $request = Request::create('/');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15');
        $this->assertEquals('mobile', $this->service->getDeviceType($request));

        // Test tablet
        $request = Request::create('/');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (iPad; CPU OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15');
        $this->assertEquals('tablet', $this->service->getDeviceType($request));

        // Test desktop
        $request = Request::create('/');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $this->assertEquals('desktop', $this->service->getDeviceType($request));
    }

    public function test_handles_missing_user_agent()
    {
        $request = Request::create('/');
        // No User-Agent header set
        
        $this->assertFalse($this->service->isMobile($request));
        $this->assertFalse($this->service->isTablet($request));
        $this->assertEquals('desktop', $this->service->getDeviceType($request));
    }
}
