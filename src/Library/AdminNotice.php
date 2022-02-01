<?php

namespace LicenseBridge\WordPressSDK\Library;

class AdminNotice
{
    
    /**
     * Message
     *
     * @var string
     */
    private $message;

    /**
     * Type of the message (updated, error, info)
     *
     * @var [type]
     */
    private $type;

    /**
     * Execute action admin_notice
     *
     * @param string $message
     * @param string $info
     */
    public function __construct($message, $type = 'updated')
    {
        $this->message = $message;
        $this->type = $type;

        add_action('admin_notices', array($this, 'render'));
    }

    /**
     * Renter the message
     *
     * @return void
     */
    public function render()
    {
        printf('<div class="update-nag notice inline notice-%s">%s</div>', $this->type, $this->message);
    }
}
