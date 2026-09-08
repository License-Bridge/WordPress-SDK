<?php

namespace LicenseBridge\WordPressSDK\Library;

class AdminNotice
{
    /**
     * @var array<string, array{message: string, type: string}>
     */
    private static $notices = [];

    /**
     * @var bool
     */
    private static $hookRegistered = false;

    /**
     * Queue an admin notice once per unique message per request.
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    public static function add($message, $type = 'error')
    {
        $message = (string) $message;
        $type = (string) $type;
        $key = md5($type . '|' . $message);

        if (isset(self::$notices[$key])) {
            return;
        }

        self::$notices[$key] = [
            'message' => $message,
            'type' => $type,
        ];

        if (!self::$hookRegistered) {
            self::$hookRegistered = true;
            add_action('admin_notices', [self::class, 'renderAll']);
        }
    }

    /**
     * @param string $message
     * @param string $type
     */
    public function __construct($message, $type = 'updated')
    {
        self::add($message, $type);
    }

    /**
     * @return void
     */
    public static function renderAll()
    {
        foreach (self::$notices as $notice) {
            $class = 'notice';

            if ($notice['type'] === 'error') {
                $class .= ' notice-error';
            } elseif ($notice['type'] === 'updated') {
                $class .= ' notice-success';
            } else {
                $class .= ' notice-info';
            }

            printf(
                '<div class="%s is-dismissible"><p>%s</p></div>',
                esc_attr($class),
                esc_html($notice['message'])
            );
        }
    }
}
