<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TermsController extends Controller
{
    /**
     * Display the Terms of Service.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTerm()
    {

        $termsContent = "Chào mừng bạn đến với ứng dụng của chúng tôi.\n\n" 
            . "1. Bạn đồng ý sử dụng ứng dụng cho mục đích hợp pháp.\n"
            . "2. Bạn đồng ý không sao chép hoặc phân phối bất kỳ phần nào của ứng dụng mà không có sự đồng ý của chúng tôi.\n"
            . "3. Chúng tôi có quyền chấm dứt quyền truy cập của bạn vào ứng dụng nếu bạn vi phạm bất kỳ điều khoản nào trong những điều khoản này.\n\n"
            . "Vui lòng đọc kỹ toàn bộ điều khoản sử dụng trên trang web của chúng tôi.";

        return response()->json([
            'status' => true,
            'data' => [
                'terms' => $termsContent
            ],
        ], 200);
    }
}
