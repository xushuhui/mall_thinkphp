<?php

namespace Modules\Store\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Modules\Store\Entities\Store;
use Modules\Store\Http\Requests\LoginRequest;
use Modules\Store\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     * 要求附带email和password（数据来源users表）
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * @OA\Post(path="/store/getCode", summary="获取验证码",
     *     tags={"store"},
     *     parameters={
     *      {
     *          "name" : "store_mobile",
     *          "in" : "string",
     *          "description" : "手机号",
     *          "required" : true
     *      },
     *     },
     *     @OA\Response(response="200", description="{code:0（0.成功，1.失败）,message:'ok'}"),
     *     @OA\RequestBody(@OA\MediaType(mediaType="application/json"))
     * )
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCode(Request $request)
    {
        $store_mobile = $request->input('store_mobile', '');
        if (!check_mobile($store_mobile)){
            return $this->fail(20001);
        }
        if (!Store::checkMobild($store_mobile)){
            return $this->fail(20002);
        }
        $sms_code = 123456;
        $this->setMessage(20007);
        $this->setData($sms_code);
        return $this->succeed();
    }

    /**
     * @OA\Post(path="/store/login", summary="商家登录",
     *     tags={"store"},
     *     parameters={
     *      {
     *          "name" : "shop_no",
     *          "in" : "string",
     *          "description" : "店铺号（SP+手机号）",
     *          "required" : true
     *      },
     *     {
     *          "name" : "store_mobile",
     *          "in" : "string",
     *          "description" : "手机号",
     *          "required" : true
     *      },
     *     {
     *          "name" : "password",
     *          "in" : "string",
     *          "description" : "登录密码",
     *          "required" : true
     *      },
     *     },
     *     @OA\Response(response="200", description="{code:0（0.成功，1.失败）,message:提示语}"),
     *     @OA\RequestBody(@OA\MediaType(mediaType="application/json")
     *      )
     * )
     *
     * @param \Modules\Store\Http\Requests\LoginRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        if ($this->checkPost())
        {
            // shop_no 后期再完善
            $request_data = $request->only(['store_mobile', 'password']);
            $request_data['store_mobile'] = $request->input('store_mobile', '');
            $request_data['password'] = $request->input('password', '');

            if (!Store::checkMobild($request_data['store_mobile'])){
                return $this->fail(20002);
            }

            if (!$token = Auth::guard($this->guard)->attempt($request_data)) {
                return $this->fail(20003);
            }

            $data = $this->respondWithToken($token)->{'original'};

            return $data;
        }
    }

    /**
     * @OA\Put(path="/store/register", summary="商家注册",
     *     tags={"store"},
     *     parameters={
     *     {
     *          "name" : "store_mobile",
     *          "in" : "string",
     *          "description" : "手机号",
     *          "required" : true
     *      },
     *     {
     *          "name" : "password",
     *          "in" : "string",
     *          "description" : "登录密码",
     *          "required" : true
     *      },
     *     {
     *          "name" : "password_confirmation",
     *          "in" : "string",
     *          "description" : "确认密码",
     *          "required" : true
     *      },
     *     {
     *          "name" : "invite_code",
     *          "in" : "string",
     *          "description" : "邀请码",
     *          "required" : true
     *      },
     *     {
     *          "name" : "sms_code",
     *          "in" : "string",
     *          "description" : "验证码",
     *          "required" : true
     *      },
     *     },
     *     @OA\Response(response="200", description="{code:0（0.成功，1.失败）,message:'提示语'}"),
     *     @OA\RequestBody(@OA\MediaType(mediaType="application/json")
     *      )
     * )
     *
     * @param \Modules\Store\Http\Requests\RegisterRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        if ($this->checkPost())
        {
            $request_data = $request->all();
            $request_data['store_mobile'] = $request->input('store_mobile', '');
            $request_data['password'] = $request->input('password', '');

            if (Store::checkMobild($request_data['store_mobile'])){
                return $this->fail(20004);
            }

            if (Store::register($request_data)){
                $this->setMessage(20006);
                return $this->succeed();
            }else{
                return $this->fail(20005);
            }
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me()
    {
        return $this->setData(auth($this->guard)->user());
    }

    public function reset()
    {

    }


    /**
     * @OA\Get(path="/store/logout",
     *   tags={"store"},
     *   summary="退出登录",
     *   description="退出登录",
     *   parameters={},
     *   @OA\Response(
     *     response=200,
     *     description="code:0（0.成功，1.失败）,message:'提示语'}",
     *   ),
     *     @OA\RequestBody(
     *          @OA\MediaType(mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="store-token", type="string", description="商家Token"),
     *             ))
     *      )
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth($this->guard)->logout();

        return $this->succeed();
    }

    /**
     * Refresh a token.
     * 刷新token，如果开启黑名单，以前的token便会失效。
     * 值得注意的是用上面的getToken再获取一次Token并不算做刷新，两次获得的Token是并行的，即两个都可用。
     * @return JsonResponse
     */
    public function refresh()
    {
        return $this->setData(auth($this->guard)->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return JsonResponse
     */
    protected function respondWithToken($token)
    {
        return $this->setData([
            'access_token' => $token,
            'token_type' => 'store-token',
            'expires_in'   => auth($this->guard)->factory()->getTTL() * 60
        ]);
    }

}
