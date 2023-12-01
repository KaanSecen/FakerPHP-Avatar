<?php

namespace Sabbir\Faker;

use Faker\Provider\Base as BaseProvider;
use InvalidArgumentException;

class AvatarProvider extends BaseProvider {

    public static function avatarUrl($style = "adventurer", $size = null, $slug = null, $options = []) {
        $baseUrl = "https://api.dicebear.com/7.x/";
        $url = $style;

        // Add format to the URL based on the provided options
        $format = isset($options['format']) ? $options['format'] : 'svg';
        $url .= "/$format";

        if ($slug) {
            // Use the seed as part of the URL
            $url .= "?seed=" . $slug;
        }

        // Merge additional options
        $options = array_merge([
            'size' => $size,
        ], $options);

        // Remove 'format' from the options as it's already handled in the URL
        unset($options['format']);

        $params = http_build_query(array_filter($options));

        if ($params) {
            $url .= $slug ? "&$params" : "?$params";
        }

        return $baseUrl . $url;
    }

    public static function avatar($dir = null, $fullPath = true, $style = "adventurer", $size = null, $slug = null, $options = []) {
        $dir = is_null($dir) ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible

        // Validate directory path
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        // Generate a random filename. Use the server address so that a file
        // generated at the same time on a different server won't have a collision.
        $name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
        $filename = $name . '.' . (isset($options['format']) ? $options['format'] : 'svg');
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $url = static::avatarUrl($style, $size, $slug, $options);

        // save file
        if (function_exists('curl_exec')) {
            // use cURL
            $fp = fopen($filepath, 'wb');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            fclose($fp);
            curl_close($ch);

            if (!$success) {
                unlink($filepath);
                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } elseif (ini_get('allow_url_fopen')) {
            // use remote fopen() via copy()
            copy($url, $filepath);
        } else {
            throw new RuntimeException('The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
        }

        return $fullPath ? $filepath : $filename;
    }
}
