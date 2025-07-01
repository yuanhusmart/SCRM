<?php

namespace common\helpers;

use common\errors\Code;
use Throwable;
use yii\web\Response;

class  ExceptionResponse extends Response
{
    public $format = \yii\web\Response::FORMAT_JSON;

    /**
     * @var Throwable
     */
    private $exception;

    /**
     * @var string 错误码;
     */
    private $code;

    public function exception(?Throwable $e)
    {
        $this->exception = $e;
        return $this;
    }

    public function code($code = '200000')
    {
        $this->code = $code;
        return $this;
    }

    public function content($content)
    {
        $this->content = $content;
        return $this;
    }

    protected function prepare()
    {
        if ($this->content) {
            $message = $this->content;
        } else {
            if (
                $this->exception instanceof \PDOException ||
                $this->exception instanceof \yii\db\Exception
            ) {
                $message = "操作失败, 请联系管理员, ErrCode: {$this->code}";
            } else {
                $message = "ErrCode: {$this->code}." . $this->exception->getMessage();
            }
        }

        $this->data = [
            'code'    => $this->code,
            'message' => $message,
        ];

        $this->logger();

        return parent::prepare();
    }


    private function logger()
    {
        if (!$this->exception) {
            return;
        }
        \Yii::error(
            sprintf(
                '[%s] %s',
                $this->code,
                $this->exception->__toString()
            )
        );
    }
}