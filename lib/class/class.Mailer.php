<?php

/**
 * @package SkyPHP
 * @todo    Add ability to to send Attachments
 */
class Mailer
{

    /**
     * @var string
     */
    public static $from_default = null;

    /**
     * @var string
     */
    public static $inc_dir = null;

    /**
     * @var array
     */
    public static $contents = array(
        'html' => "MIME-Verson: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\n",
        'text' => ''
    );

    /**
     * @var array
     */
    public $to = array();

    /**
     * @var string
     */
    public $from;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $body;

    /**
     * @var string
     */
    public $reply_to;

    /**
     * @var array
     */
    public $cc = array();

    /**
     * @var array
     */
    public $bcc = array();

     /**
     * @var string
     */
    public $headers;

    /**
     * @var string
     */
    public $content_type;

    /**
     * Sets properties based on args if they are set
     * @param   mixed   $to
     * @param   string  $subject
     * @param   string  $body
     * @param   string  $from
     */
    public function __construct($to = null, $subject = null, $body = null, $from = null)
    {
        $this->from = self::$from_default;

        if ($to) {
            $this->addTo($to);
        }

        if ($subject) {
            $this->subject = $subject;
        }

        if ($body) {
            $this->body = $body;
        }

        if ($from) {
            $this->from = $from;
        }
    }

    /**
     * Sets the default FROM value
     * @param   string  $from
     */
    public static function setDefaultFrom($from)
    {
        self::$from_default = $from;
    }

    /**
     * Sets from
     * @param   string  $s
     * @return  $this
     */
    public function setFrom($s)
    {
        $this->from = $s;
        return $this;
    }

    /**
     * Sets reply to
     * @param   string  $s
     * @return  $this
     */
    public function setReplyTo($s)
    {
        $this->reply_to = $s;
        return $this;
    }

    /**
     * Sets the subject
     * @param   string  $s
     * @return  $this
     */
    public function setSubject($s)
    {
        $this->subject = $s;
        return $this;
    }

    /**
     * Sets the body
     * @param   string  $s
     * @return  $this
     */
    public function setBody($s)
    {
        $this->body = $s;

        return $this;
    }

    /**
     * Makes the headers string and returns it... sets $this->headers property
     * @return  string
     * @throws  Exception   if FROM not set
     */
    public function makeHeaders()
    {
        if ($this->headers) {
            return $this->headers;
        }

        if (!$this->from) {
            throw new Exception('Mailer expects from to be specified before sending an email.');
        }

        $this->headers = $this->content_type;

        if ($this->from) {
            $this->headers .= 'From: '.$this->from."\r\n";
        }

        if ($this->reply_to) {
            $this->headers .= 'Reply-To: ' . $this->reply_to . "\r\n";
        }

        foreach ($this->cc as $cc) {
            $this->headers .= 'Cc: '.$cc."\r\n";
        }

        foreach ($this->bcc as $bcc) {
            $this->headers .= 'Bcc: '.$bcc."\r\n";
        }

        return $this->headers;
    }

    /**
     * Sets the content type
     * @param string  $type
     * @return  $this
     */
    public function setContentType($type)
    {
        $this->content_type = self::$contents[$type];
        return $this;
    }

    /**
     * @param   ...     emails
     * @return  $this
     */
    public function addCc()
    {
        return $this->_append('cc', func_get_args());
    }

    /**
     * @param   ...     emails
     * @return  $this
     */
    public function addBcc()
    {
        return $this->_append('bcc', func_get_args());
    }

    /**
     * @param   ...     emails
     * @return  $this
     */
    public function addTo()
    {
        return $this->_append('to', func_get_args());
    }

    /**
     * Pushes args onto the array
     * @param   string  $arr
     * @param   array   $args
     * @return  $this
     */
    private function _append($arr, $args)
    {
        foreach ($args as $arg) {
            $arg = arrayify($arg);
            foreach ($arg as $a) {
                $this->{$arr}[] = $a;
            }
        }

        return $this;
    }

    /**
     * @return  string
     */
    public function makeSubject()
    {
        return $this->subject ?: '(no subject)';
    }

    /**
     * @return  string
     */
    public function makeTo()
    {
        return implode(',', $this->to);
    }

    /**
     * Sends the email
     * @return  Boolean
     */
    public function send()
    {
        return @mail(
            $this->makeTo(),
            $this->makeSubject(),
            $this->body,
            $this->makeHeaders()
        );
    }

    /**
     * Includes the template and sets the body of the email with it
     * @param   string  $name   name of template or path to php file
     * @param   array   $data
     * @return  $this
     * @throws  Exception   if using a Mailer template and there is no inc_dir
     * @throws  Excpetion   if the file to include does not exist
     */
    public function inc($name, array $data = array())
    {
        if (strpos($name, '.php')) {
            $include = $name;
        } else {
            if (!self::$inc_dir) {
                throw new Exception('Mailer::$inc_dir not set.');
            }

            $include = self::$inc_dir . $name . '.php';
        }

        if (!file_exists_incpath($include)) {
            throw new Exception('Mailer "' . $include . '" does not exist');
        }

        return $this->setBody($this->_includeTemplate($include, $data));
    }

    /**
     * Includes the path in the scope of the Mailer
     * @param   string  $_include
     * @param   mixed   $data   should be an associative array or stdClass
     * @return  string
     */
    private function _includeTemplate($_include, $data)
    {
        ob_start();
        include $_include;
        $r = ob_get_contents();
        ob_end_clean();
        return $r;
    }

}
