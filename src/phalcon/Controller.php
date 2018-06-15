<?php

namespace CodeBase;

use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Phalcon\Mvc\Controller as PhController;
use PHPLib\Logging\Logger;
use Swagger\Annotations as SWG;

/**
 * Class Controller
 *
 * @property \CodeBase\Utils $utils
 *
 *
 * @SWG\Swagger(
 *     schemes={"http"},
 *     host="api.host.com",
 *     basePath="/",
 *     @SWG\Info(
 *         version="1.0.0",
 *         title="This is my website cool API",
 *         description="Api description...",
 *         termsOfService="",
 *         @SWG\Contact(
 *             email="contact@mysite.com"
 *         )
 *     ),
 *     @SWG\SecurityScheme(
 *         securityDefinition="JwtBearerToken",
 *         type="apiKey",
 *         name="Authorization",
 *         in="header",
 *         description="JWT Token",
 *     ),
 *     @SWG\SecurityScheme(
 *         securityDefinition="KongApiKey",
 *         type="apiKey",
 *         name="apiKey",
 *         in="header",
 *         description="Kong API Key",
 *     ),
 * )
 */
class Controller extends PhController
{
    /**
     * @var User
     */
    private $user;

    /**
     * App config from Phalcon\DiInterface
     */
    private $config;

    public function onConstruct()
    {
        /**
         * Load config from DI
         */
        $this->config = $this->getDI()->get('config');


        $this->validateCors();

        /**
         * Handle preflight request
         */
        if ($this->request->isOptions()) {
            $this->response->sendHeaders();
            exit;
        }


        /**
         * JWT token validation
         * NOTE: Some API need token, some API doesn't need.
         */
        $authorizationHeader = explode(' ', $this->request->getHeader('Authorization'), 2); // Authorization: Bearer <token>

        if (isset($authorizationHeader[1])) {
            $jwtToken = $authorizationHeader[1];

            try {
                $jwtPayload = (array)JWT::decode($jwtToken, $this->config->jwt->secretKey, ['HS256']);

                if (!isset($jwtPayload['user_code'])) {
                    $this->responseSystemMessage(CommonConstant::FORBIDDEN, 'Invalid token');
                }

                $this->user = new User([
                    'code' => $jwtPayload['user_code'],
                    'name' => $jwtPayload['user_name'],
                    'division_code' => $jwtPayload['division_code'],
                ]);
            } catch (SignatureInvalidException $exception) {
                $this->responseSystemMessage(CommonConstant::FORBIDDEN, 'Invalid token');
            } catch (\Exception $exception) {
                $this->responseSystemMessage(CommonConstant::FORBIDDEN, 'Invalid token');
            }
        }
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        return $this->user;
    }

    /**
     * Response either success or error response along with response data http status code 200
     * @param $data
     */
    public function outputJSON($data)
    {
        $response = [
            'code' => CommonConstant::SUCCESS,
            'message' => 'OK',
            'data' => $data,
        ];

        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setJsonContent($response);

        $this->response->send();
        $start_time = $this->getStartTime();
        Logger::apiFinish($start_time);
        exit;
    }

    public function responseError($error)
    {
        $this->responseAppError($error['code'], $error['message']);
    }

    public function responseAppError($code, $message)
    {
        $response = [
            'code'    => $code,
            'message' => $message,
        ];

        $this->response->setJsonContent($response);
        $this->response->send();
        Logger::error($message);
        exit();
    }

    /**
     * Response error code in case of: System error
     *
     * @param $code
     * @param $message
     */
    private function responseSystemMessage($code, $message)
    {
        $this->response->setStatusCode($code);
        $this->response->setContent($message);
        $this->response->send();
        Logger::warning($message, $this->getStartTime());
        exit();
    }

    /**
     * Turn on CORS validation
     * Support pre-flight request
     * Allow access to only some specified domain
     */
    private function validateCors()
    {
        $origin = $this->request->getHeader('Origin');

        // Don't need to validate CORS if no origin
        if (!strlen($origin)) {
            return;
        }

        if ($this->config->cors->validation) {
            if (!in_array($origin, (array)$this->config->cors->validOrigins)) {
                $message = "[CORS] Invalid Origin: `$origin`. Contact your administrator.";
                $this->response->setStatusCode(CommonConstant::FORBIDDEN);
                $this->response->setContent($message);
                $this->response->send();
                Logger::warning($message, $this->getStartTime());
                exit();
            }

            $this->response->setHeader("Access-Control-Allow-Origin", $origin);
            $this->response->setHeader('Access-Control-Allow-Headers', 'Token, X-Requested-With');
        } else {
            $this->response->setHeader('Access-Control-Allow-Origin', '*');
            $this->response->setHeader('Access-Control-Allow-Headers', 'Token, X-Requested-With');
        }
    }

    public function getStartTime()
    {
        return $this->getDI()->getShared('registry')->executionTime;
    }
}
