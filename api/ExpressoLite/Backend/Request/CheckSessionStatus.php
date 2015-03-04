<?php
/**
 * Expresso Lite
 * Handler for EchoParams calls.
 * It checks if the current tine session is logged in
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class CheckSessionStatus extends LiteRequest
{

    /**
     * @var STATUS_INACTIVE Constant that indicates that
     *      there is NOT a currently estabilished session
     */
    const STATUS_INACTIVE = 'inactive';

    /**
     * @var STATUS_ACTIVE Constant that indicates that there is
     *     a currently estabilished session
     */
    const STATUS_ACTIVE = 'active';

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $loggedIn = $this->tineSession->isLoggedIn();

        if ($loggedIn) {
            // the session may have been expired on Tine.
            // So we check if it's still there
            $result = $this->tineSession->jsonRpc('Tinebase.void', (object) array(), true);
            if (isset($result->error)) {
                // we assume the only possible error is to be logged off
                $status = self::STATUS_INACTIVE;
            } else {
                $status = self::STATUS_ACTIVE;
            }
        } else {
            $status = self::STATUS_INACTIVE;
        }

        return (object) array(
            'status' => $status
        );
    }

    /**
     * Allows this request to be executed even without a previously
     * estabilished TineSession.
     *
     * @return true.
     */
    public function allowAccessWithoutSession()
    {
        return true;
    }
}
