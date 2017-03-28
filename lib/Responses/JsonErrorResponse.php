<?php namespace Responses;

use Phalcon\Http\Response;

class JsonErrorResponse extends Response
{
    public function __construct($content = null, $code = null, $status = null)
    {
        parent::__construct($content, $code, $status);

        $this->setStatusCode(500);
        $this->setJsonContent(['status' => 'ERROR', 'message' => $content]);
    }
}