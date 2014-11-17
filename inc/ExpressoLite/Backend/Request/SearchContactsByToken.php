<?php
/**
 * Expresso Lite
 * Handler for searchContactsByToken calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class SearchContactsByToken extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $token = $this->param('token');
        $response = $this->jsonRpc('Addressbook.searchContacts', (object) array(
            'filter' => array(
                (object) array(
                    'condition' => 'OR',
                    'filters' => array(
                        (object) array(
                            'condition' => 'AND',
                            'filters' => array(
                                (object) array(
                                    'field' => 'query',
                                    'id' => 'ext-record-5',
                                    'operator' => 'contains',
                                    'value' => $token
                                ),
                                (object) array(
                                    'field' => 'container_id',
                                    'id' => 'ext-record-6',
                                    'operator' => 'in',
                                    'value' => array(
                                        '48480'
                                    )
                                )
                            ),
                            'id' => 'ext-comp-1023',
                            'label' => 'Contatos'
                        )
                    )
                )
            ),
            'paging' => (object) array(
                'dir' => 'ASC',
                'limit' => 50,
                'sort' => 'n_fileas',
                'start' => 0
            )
        ));
        $contacts = array();
        foreach ($response->result->results as $contact) {
            $contacts[] = (object) array(
                'cpf' => $contact->id, // yes, returned object is inconsistent, see searchContactsByEmail()
                'email' => $contact->email,
                'name' => $contact->n_fn,
                'isDeleted' => $contact->is_deleted !== '',
                'org' => $contact->org_name
            );
        }
        return $contacts;
    }
}
