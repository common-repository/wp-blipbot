<?php

/**
 * Blip! (http://blip.pl) communication library.
 *
 * @author Marcin Sztolcman <marcin /at/ urzenia /dot/ net>
 * @version 0.02.30
 * @version $Id: blipapi_picture.php 133 2010-01-06 16:46:21Z urzenia $
 * @copyright Copyright (c) 2007, Marcin Sztolcman
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License v.2
 * @package blipapi
 */

/**
 * Blip! (http://blip.pl) communication library.
 *
 * @author Marcin Sztolcman <marcin /at/ urzenia /dot/ net>
 * @version 0.02.30
 * @version $Id: blipapi_picture.php 133 2010-01-06 16:46:21Z urzenia $
 * @copyright Copyright (c) 2007, Marcin Sztolcman
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License v.2
 * @package blipapi
 */

if (!class_exists ('BlipApi_Picture')) {
    class BlipApi_Picture extends BlipApi_Abstract implements IBlipApi_Command {
        /**
         * ID of item to read
         *
         * @access protected
         * @var int
         */
        protected $_id;

        /**
         * Include some additional data in respond to read method.
         * More info: http://www.blip.pl/api-0.02.html#parametry
         *
         * @access protected
         * @var string|array
         */
        protected $_include;

        /**
         * Limit read results to $_limit items
         *
         * @access protected
         * @var int
         */
        protected $_limit       = 10;

        /**
         * Offset for read result set
         *
         * @access protected
         * @var int
         */
        protected $_offset      = 0;

        /**
         * ID of item where data is being set.
         *
         * @access protected
         * @var int
         */
        protected $_since_id;

        /**
         * Setter for field: id
         *
         * @param string $value
         * @access protected
         */
        protected function __set_id ($value) {
            $this->_id = $this->__validate_int ($value, 'ID');
        }

        /**
         * Setter for field: include
         *
         * @param string $value
         * @access protected
         */
        protected function __set_include ($value) {
            $this->_include = $this->__validate_include ($value);
        }

        /**
         * Setter for field: limit
         *
         * @param string $value
         * @access protected
         */
        protected function __set_limit ($value) {
            $this->_limit = $this->__validate_limit ($value);
        }

        /**
         * Setter for field: offset
         *
         * @param string $value
         * @access protected
         */
        protected function __set_offset ($value) {
            $this->_offset = $this->__validate_int ($value, 'offset');
        }

        /**
         * Setter for field: since_id
         *
         * @param string $value
         * @access protected
         */
        protected function __set_since_id ($value) {
            $this->_since_id = $this->__validate_int ($value, 'since ID');
        }

        /**
         * Read picture attached to status/message/update
         *
         * Throws InvalidArgumentException when update ID is missing
         *
         * @access public
         * @return array parameters for BlipApi::__call
         */

        public function read () {
            if ($this->_since_id) {
                $url = "/pictures/$this->_since_id/all_since";
            }
            else if ($this->_id) {
                $url = "/updates/$this->_id/pictures";
            }
            else {
                $url = "/pictures/all";
            }

            $params = array ();
            if ($this->_limit) {
                $params['limit'] = $this->_limit;
            }
            if ($this->_offset) {
                $params['offset'] = $this->_offset;
            }
            if ($this->_include) {
                $params['include'] = implode (',', $this->_include);
            }

            return array ($url, 'get', $params);
        }
    }
}

