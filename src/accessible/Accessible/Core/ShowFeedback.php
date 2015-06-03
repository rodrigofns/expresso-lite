<?php
/**
 * Expresso Lite Accessible
 * Reads an email.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Core;

use Accessible\Handler;
use Accessible\Dispatcher;
use ExpressoLite\Backend\LiteRequestProcessor;

class ShowFeedback extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $this->showTemplate('ShowFeedbackTemplate', (object) array(
            'message' =>  $params->message,
            'destinationText' => $params->destinationText,
            'destinationUrl' => $this->makeUrl($params->destinationUrl->action,
                isset($params->destinationUrl->params) ? $params->destinationUrl->params : array()
             )
        ));
    }
}
