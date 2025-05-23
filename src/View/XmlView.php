<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\Utility\Xml;
use Cake\View\Exception\SerializationFailureException;

/**
 * A view class that is used for creating XML responses.
 *
 * By setting the 'serialize' option in view builder of your controller, you can specify
 * a view variable that should be serialized to XML and used as the response for the request.
 * This allows you to omit views + layouts, if your just need to emit a single view
 * variable as the XML response.
 *
 * In your controller, you could do the following:
 *
 * ```
 * $this->set(['posts' => $posts]);
 * $this->viewBuilder()->setOption('serialize', true);
 * ```
 *
 * When the view is rendered, the `$posts` view variable will be serialized
 * into XML.
 *
 * **Note** The view variable you specify must be compatible with Xml::fromArray().
 *
 * You can also set `'serialize'` as an array. This will create an additional
 * top level element named `<response>` containing all the named view variables:
 *
 * ```
 * $this->set(compact('posts', 'users', 'stuff'));
 * $this->viewBuilder()->setOption('serialize', true);
 * ```
 *
 * The above would generate an XML object that looks like:
 *
 * `<response><posts>...</posts><users>...</users></response>`
 *
 * You can also set `'serialize'` to a string or array to serialize only the
 * specified view variables.
 *
 * If you don't set the `serialize` option, you will need a view. You can use extended
 * views to provide layout like functionality.
 */
class XmlView extends SerializedView
{
    /**
     * XML layouts are located in the `layouts/xml/` subdirectory
     *
     * @var string
     */
    protected string $layoutPath = 'xml';

    /**
     * XML views are located in the 'xml' subdirectory for controllers' views.
     *
     * @var string
     */
    protected string $subDir = 'xml';

    /**
     * Default config options.
     *
     * Use ViewBuilder::setOption()/setOptions() in your controller to set these options.
     *
     * - `serialize`: Option to convert a set of view variables into a serialized response.
     *   Its value can be a string for single variable name or array for multiple
     *   names. If true all view variables will be serialized. If null or false
     *   normal view template will be rendered.
     * - `xmlOptions`: Option to allow setting an array of custom options for Xml::fromArray().
     *   For e.g. 'format' as 'attributes' instead of 'tags'.
     * - `rootNode`: Root node name. Defaults to "response".
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'serialize' => null,
        'xmlOptions' => null,
        'rootNode' => null,
    ];

    /**
     * Mime-type this view class renders as.
     *
     * @return string The JSON content type.
     */
    public static function contentType(): string
    {
        return 'application/xml';
    }

    /**
     * @inheritDoc
     */
    protected function _serialize(array|string $serialize): string
    {
        /** @var string $rootNode */
        $rootNode = $this->getConfig('rootNode', 'response');

        if (is_array($serialize)) {
            if (!$serialize) {
                $serialize = '';
            } elseif (count($serialize) === 1) {
                $serialize = current($serialize);
            }
        }

        if (is_array($serialize)) {
            $data = [$rootNode => []];
            foreach ($serialize as $alias => $key) {
                if (is_numeric($alias)) {
                    $alias = $key;
                }
                if (array_key_exists($key, $this->viewVars)) {
                    $data[$rootNode][$alias] = $this->viewVars[$key];
                }
            }
        } else {
            /** @var array<mixed>|string|int|bool|null $data */
            $data = $this->viewVars[$serialize] ?? [];
            if (
                $data !== null &&
                (!is_array($data) || Hash::numeric(array_keys($data)))
            ) {
                $data = [$rootNode => [$serialize => $data]];
            }
        }

        $options = $this->getConfig('xmlOptions', []);
        if (Configure::read('debug')) {
            $options['pretty'] = true;
        }

        /**
         * @var array<mixed> $data
         * @var string|false $result
         */
        $result = Xml::fromArray($data, $options)->saveXML();
        if ($result === false) {
            throw new SerializationFailureException(
                'XML serialization of View data failed.',
            );
        }

        return $result;
    }
}
