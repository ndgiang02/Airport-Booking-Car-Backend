<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IntroController extends Controller
{
    /**
     * Display the introduction about the application.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIntrodution()
    {
        $introContent = "Chào mừng bạn đến với ứng dụng của chúng tôi!\n"
            . "Ứng dụng này được thiết kế để cung cấp các giải pháp tốt nhất cho người dùng.\n\n"
            . "Chúng tôi cam kết mang đến trải nghiệm tốt nhất và liên tục cải thiện để đáp ứng nhu cầu của bạn.\n\n"
            . "Cảm ơn bạn đã sử dụng ứng dụng của chúng tôi!";

        return response()->json([
            'status' => true,
            'message' => "Successfully",
            'data' => [
                'intro' => $introContent,
            ],
        ], 200);
    }
}
