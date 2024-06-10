<?php

namespace App\Baccarat\Service\Platform\Bacc;

use GuzzleHttp\Cookie\CookieJar;
use Hyperf\Contract\ConfigInterface;

class CookieJarFactory
{

    protected ?CookieJar $cookieJar = null;

    public function __construct(protected ConfigInterface $config)
    {

    }

    public function getCookie(): CookieJar
    {
        return $this->cookieJar ??= $this->createCookie();
    }

    protected function createCookie(): CookieJar
    {
        // 构建 cookie
        $cookie = $this->config->get('baccarat.platform.bacc.cookie');

        if (empty($cookie)) {
            throw new \InvalidArgumentException('cookie is empty');
        }

        if (is_string($cookie)) {
            return new CookieJar(cookieArray: $this->getCookieWithFilePath($cookie));
        }

        if (is_array($cookie)) {
            return new CookieJar(cookieArray:$cookie);
        }

        throw new \InvalidArgumentException('cookie error');
    }


    protected function getCookieWithFilePath(string $filePath):array
    {
        // 判断 cookie 文件是否存在
        if ($filePath && file_exists($filePath)) {

            // 读取 cookie 文件内容
            $cookie = file_get_contents($filePath);

            $cookie = explode("\n", $cookie);

            // 判断 cookie 文件内容是否为空
            if (!empty($cookie)) {
                // 构建 cookie
                return $cookie;
            }
        }
        return [];
    }
}