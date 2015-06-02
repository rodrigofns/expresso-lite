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
     * @var MSG_SUCCESS.
     */
    const MSG_SUCCESS = 'feedbackMessageSuccess';

    /**
     * @var MSG_ERROR.
     */
    const MSG_ERROR = 'feedbackMessageError';

    /**
     * @var MSG_ALERT.
     */
    const MSG_ALERT = 'feedbackMessageAlert';

    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $this->showTemplate('ShowFeedbackTemplate', (object) array(
            'typeMsg' => isset($params->typeMsg) ? $params->typeMsg : ShowFeedback::MSG_SUCCESS,
            'message' =>  $params->message,
            'destinationText' => $params->destinationText,
            'destinationUrl' => $this->makeUrl($params->destinationUrl->action,
                isset($params->destinationUrl->params) ? $params->destinationUrl->params : array()
             )
        ));
    }
}