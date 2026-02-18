<?php

declare(strict_types=1);

namespace Mileena\Web;

use Mileena\Config;

class WebApp
{
    private string $controller;
    private string $controllerMethod;

    private static $argList = [];

    public Config $config;
    public Debugger $debugger;
    private static self $instance;

    public function __construct(Config $config)
    {
        $this->config = $config;

        $this->debugger = new Debugger($this->config);

        $this->controller = $config->get('app.default_controller');
        $this->controllerMethod  = $config->get('app.default_controller_method');

        self::$instance = $this;

        $urlPaths = explode("/", trim($_SERVER['REDIRECT_URL'] ?? ''));

        if (count($urlPaths) >= 2) {
            if (!empty($urlPaths[1])) {
                $this->controller = $urlPaths[1];
            }

            if (!empty($urlPaths[2])) {
                $this->controllerMethod = $urlPaths[2];
            }
        }

        register_shutdown_function(function (): void {
            \Mileena\DBMQ\DBM::closeConnection();
        });
    }

    public function webRoute(): void
    {
        $controllerMap = $this->config->get('app.controllers');

        if (!empty($controllerMap[$this->controller])) {
            $controllerPath =  $this->config->get('app.controller_namespace') . $controllerMap[$this->controller] . 'Controller';

            $controller = new $controllerPath();

            if (method_exists($controller, $this->controllerMethod)) {
                $refMethod = new \ReflectionMethod($controller, $this->controllerMethod);

                if (!$refMethod->isPublic()) {
                    http_response_code(404);

                    return;
                }

                $isAllowPublicAccess = $controller instanceof AllowPublicAccess;
                $isAllowPublicMethod = !empty($refMethod->getAttributes(AllowPublicAccess::class));

                if (!$isAllowPublicAccess && !$isAllowPublicMethod) {
                    Auth::protect();
                }

                $params = $refMethod->getParameters();

                self::$argList = [];

                if (!empty($params)) {
                    foreach ($params as $arg) {
                        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $arg->getName()));
                        $v = null;

                        if ($arg->isDefaultValueAvailable()) {
                            $v = $arg->getDefaultValue();
                        } else {
                            if ($arg->allowsNull()) {
                                $v = null;
                            }
                        }

                        if (!empty($_REQUEST[$key])) {
                            $v = $_REQUEST[$key];
                        }

                        self::$argList[$arg->getName()] = $v;
                    }
                }

                $refMethod->invokeArgs($controller, self::$argList);
            } else {
                http_response_code(404);
            }
        }
    }

    public static function getInstance(): self
    {
        return self::$instance ?? throw new \RuntimeException("Mileena App not init!");
        ;
    }

    public static function getArgList(): array
    {
        return self::$argList;
    }
}
