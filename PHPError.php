<?php
// Quando ocorrer um erro (notice, warning, etc), mostrar a mensagem e
// encerrar a execução do script. Muito útil porque limpa qualquer conteúdo
// e mostra somente a mensagem de erro e nada mais. Em produção, nenhum erro
// será exibido e as mensagens serão armazenadas no log (configurado no PHP.ini)

class PHPError {

    public $number;
    public $msg;
    public $file;
    public $line;
    public $via;
    public $showStackTrace = true;

    public function __construct() {
        register_shutdown_function(function() {
            $error = error_get_last();

            if (is_null($error)) {
                return;
            }

            $this->number = $error['type'];
            $this->msg = $error['message'];
            $this->file = $error['file'];
            $this->line = $error['line'];
            $this->via = 'register_shutdown_function';

            $this->showError();
        });

        set_error_handler(function($errno, $errstr, $errfile, $errline) {

            $this->number = $errno;
            $this->msg = $errstr;
            $this->file = $errfile;
            $this->line = $errline;
            $this->via = 'set_error_handler';

            $this->showError();
        });
    }

    public function showError() {

        $error_reporting = error_reporting();

        // Erros suprimidos com @ não serão mostrados nem logados
        if (!$error_reporting || !$this->number) {
            return;
        }

        $time = $_SERVER['REQUEST_TIME_FLOAT'];

        switch ($this->number) {
            case E_ERROR:
                $code = 'E_ERROR';
                $type = 'Fatal error';
                $this->showStackTrace = false;
                break;

            case E_WARNING:
                $code = 'E_WARNING';
                $type = 'Warning';
                break;

            case E_PARSE:
                $code = 'E_PARSE';
                $type = 'Parse error';
                break;

            case E_NOTICE:
                $code = 'E_NOTICE';
                $type = 'Notice';
                break;

            case E_CORE_ERROR:
                $code = 'E_CORE_ERROR';
                $type = '(Core) Fatal error';
                break;

            case E_CORE_WARNING:
                $code = 'E_CORE_WARNING';
                $type = '(Core) Warning';
                break;

            case E_COMPILE_ERROR:
                $code = 'E_COMPILE_ERROR';
                $type = '(Compile) Fatal error';
                break;

            case E_COMPILE_WARNING:
                $code = 'E_COMPILE_WARNING';
                $type = '(Compile) Warning';
                break;

            case E_USER_ERROR:
                $code = 'E_USER_ERROR';
                $type = '(User) Fatal error';
                break;

            case E_USER_WARNING:
                $code = 'E_USER_WARNING';
                $type = '(User) Warning';
                break;

            case E_USER_NOTICE:
                $code = 'E_USER_NOTICE';
                $type = '(User) Notice';
                break;

            case E_STRICT:
                $code = 'E_STRICT';
                $type = 'Strict standards';
                break;

            case E_RECOVERABLE_ERROR:
                $code = 'E_RECOVERABLE_ERROR';
                $type = 'Catchable fatal error';
                break;

            case E_DEPRECATED:
                $code = 'E_DEPRECATED';
                $type = 'Deprecated';
                break;

            case E_USER_DEPRECATED:
                $code = 'E_USER_DEPRECATED';
                $type = '(User) Deprecated';
                break;

            default:
                $code = 'UNKNOWN';
                $type = 'Unknown error';
        }

        if ($this->showStackTrace) {
            $e = new \Exception();
            $stack = $e->getTraceAsString();
            $this->msg .= "\n\n{$stack}";
        }

        // Limpar todo o erro nativo do PHP
        // OBS.: No PHP.ini, usar: output_buffering = On
        while (ob_get_contents()) {
            ob_end_clean();
        }

        header('HTTP/1.1 500 Server Error');
        header('Content-Type: text/html');

        $tpl = file_get_contents(__DIR__ . '/php-error.html');
        $tpl = str_replace('{TYPE}', $type, $tpl);
        $tpl = str_replace('{MSG}', nl2br(htmlspecialchars($this->msg)), $tpl);
        $tpl = str_replace('{FILE}', $this->file, $tpl);
        $tpl = str_replace('{LINE}', $this->line, $tpl);
        $tpl = str_replace('{ERROR_CODE}', $code, $tpl);
        $tpl = str_replace('{ERROR_NUMBER}', $this->number, $tpl);
        $tpl = str_replace('{ERROR_REPORTING}', $error_reporting, $tpl);
        $tpl = str_replace('{TIME}', $time, $tpl);
        $tpl = str_replace('{VIA}', $this->via, $tpl);

        exit($tpl);
    }
}

// Não mostrar erros customizados pela linha de comando
if (PHP_SAPI !== 'cli') {
    new PHPError();
}
