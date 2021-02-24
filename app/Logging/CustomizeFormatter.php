<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
//use Monolog\Logger;
//use Monolog\Processor\IntrospectionProcessor;

class CustomizeFormatter
{

    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(null, null, true, true));
            // $handler->setFormatter(new LineFormatter('[%datetime%] %channel%.%level_name%: %message% %context% %extra%'));
            //$handler->pushProcessor(new IntrospectionProcessor(Logger::DEBUG, ['\Illuminate']));
        }
    }
}
