<?php

/* This document is meant to follow PSR-1 and PSR-2 php-fig standards
   http://www.php-fig.org/psr/psr-1/
   http://www.php-fig.org/psr/psr-2/ */

/**
 * Abstract List with (Type) Restrictions
 * 
 * This file contains the AbstractRestrictedList class.  This class inherits
 * from Phabstractic\Data\Types\AbstractList so it has all the features.
 * It is composed of a Phabstractic\Data\Types\Restrictions predicate object
 * along with the usual options array.  This class checks all incoming data
 * to make sure it fits the restrictions defined in the restrictions member.
 * 
 * @copyright Copyright 2016 Asher Wolfstein
 * @author Asher Wolfstein <asherwunk@gmail.com>
 * @link http://wunk.me/ The author's URL
 * @link http://wunk.me/programming-projects/phabstractic/ Framework URL
 * @license http://opensource.org/licenses/MIT
 * @package Data
 * @subpackage Resource
 * 
 */

/**
 * Falcraft Libraries Data Types Resource Namespace
 * 
 */
namespace Phabstractic\Data\Types\Resource
{
    require_once(realpath( __DIR__ . '/../../../') . '/falcraftLoad.php');
    
    $includes = array(// we are configurable
                      '/Features/ConfigurationTrait.php',
                      // we inherit the functionality of the abstract list
                      '/Data/Types/Resource/AbstractList.php',
                      // we filter the values in the list
                      '/Data/Types/Resource/FilterInterface.php',
                      // we instantiate default restricitons when none are given
                      '/Data/Types/Restrictions.php',
                      // we throw these exceptions
                      '/Data/Types/Exception/InvalidArgumentException.php',);
    
    falcraftLoad($includes, __FILE__);
    
    use Phabstractic\Data\Types;
    use Phabstractic\Data\Types\Exception as TypesException;
    use Phabstractic\Features;
    
    /**
     * Restricted List Abstract Class - Defines a basic list class with element
     * restrictions, implements ListInterface
     * 
     * Inherits from AbstractList.  This places restrictions on list values
     * using the Restrictions predicate data type.
     * 
     * CHANGELOG
     * 
     * 1.0: Created AbstractRestrictedList - May 16th, 2013
     * 1.1: Clarified Documentation - October 7th, 2013
     * 2.0: Refactored and re-formatted for inclusion
     *          in primus - April 11th, 2015
     * 3.0: reformatted for inclusion in phabstractic - July 21st, 2016
     * 
     * @uses Phabstractic\Data\Types\Resource\AbstractList
     * 
     * @version 3.0
     * 
     */
    abstract class AbstractRestrictedList extends AbstractList
    {
        use Features\ConfigurationTrait;
        
        /**
         * The restrictions to be placed on the data types
         * 
         * Defined as a Phabstractic\Data\Types\Resource\FilterInterface object
         * 
         * @var Phabstractic\Data\Types\Resource\FilterInterface
         *          The data restrictions
         * 
         */
        protected $restrictions = null;
        
        /**
         * Return the current restrictions used by the restricted list
         * 
         * NOTE: you can only set the restrictions in the constructor
         * 
         * @return Phabstractic\Data\Types\Resource\FilterInterface
         * 
         */
        public function getRestrictions()
        {
            return $this->restrictions;
        }
        
        /**
         * AbstractRestrictedList Constructor
         * 
         * Populates the internal member array, as well as the Predicate object
         * 
         * Note: if no restrictions predicate object is given, a predicate object
         *       inclusive of all types (except Type::TYPED_OBJECT) is generated
         * 
         * Creates an empty list if no parameter given.
         * 
         * Options: strict - Do we raise appropriate exceptions when values
         *                   are misaligned?
         * 
         * @param mixed $data The data to populate the internal member array
         * @param Phabstractic\Data\Types\Resource\FilterInterface $restrictions
         *            The predicate type object
         * @param array $options The options for the array
         * 
         * @throws Exception\InvalidArgumentException If an illegal value is
         *             given to the object (not in restrictions)
         * 
         */
        public function __construct(
            $data = null,
            FilterInterface $restrictions = null,
            $options = array()) 
        {
            $this->configure($options);
            
            // If there are no restrictions given, build basic free form restrictions
            // Default doesn't allow Type::TYPED_OBJECT    
            if (!$restrictions) {
                $restrictions = Types\Restrictions::getDefaultRestrictions();
            }
            
            $this->restrictions = $restrictions;
            if ( is_array( $data ) ) {
                // Plain Array
                
                /* Check input values for any illegal types
                   If false, throw error because this is a constructor and can't
                   really return anything. */
                if (!$this->restrictions::checkElements(
                        $data,
                        $this->restrictions,
                        $this->conf->strict)) {
                    throw new TypesException\InvalidArgumentException(
                        'Phabstractic\\Data\\Types\\Resource\\' .
                        'AbstractRestrictedList->__construct: Illegal Value');
                }
            } else if ($data instanceof AbstractList) {
                // Phabstractic\Data\Types\Resource\AbstractList
                    
                /* Check input values for any illegal types
                   If false, throw error because this is a constructor and can't
                   really return anything. */
                if (!$this->restrictions::checkElements(
                        $data->getList(),
                        $this->restrictions,
                        $this->conf->strict)) {
                    throw new TypesException\InvalidArgumentException(
                        'Phabstractic\\Data\\Types\\Resource\\' .
                        'AbstractRestrictedList->__construct: Illegal Value');
                }
            } else if ($data) {
                // A scalar value
                
                /* Check input values for any illegal types
                   If false, throw error because this is a constructor and can't
                   really return nothing */
                if (!$this->restrictions::checkElements(
                        array($data),
                        $this->restrictions,
                        $this->conf->strict)) {
                    throw new TypesException\InvalidArgumentException(
                        'Phabstractic\\Data\\Types\\Resource\\' .
                        'AbstractRestrictedList->__construct: Illegal Value');
                }
            }
            
            parent::__construct($data);
        }
        
        
        /**
         * Checks a value to see if it is in restrictions
         * 
         * @param mixed $values,.. The values to check
         * 
         * @return boolean Allowed or not?
         * 
         * @throws Exception\InvalidArgumentException if value not in
         *             restrictions, and strict option is set
         * 
         */
        protected function check()
        {
            if (!$this->restrictions::checkElements(
                    func_get_args(),
                    $this->restrictions,
                    $this->conf->strict)) {
                if ($this->conf->strict) {
                    throw new TypesException\InvalidArgumentException(
                        'Phabstractic\\Data\\Types\\Resource\\' .
                        'AbstractRestrictedList->check: Value not in ' .
                        'restrictions' );
                } else {
                    return false;
                }
                
            }
            
            return true;
        }
        
        /**
         * Check whether a value is allowed or not before pushing
         * 
         * NOTE: Pushing must be implemented by child object
         *       This only checks, doesn't actually push
         * 
         * @return bool Allowed or not?
         * 
         */
        public function push()
        {
            $args = func_get_args();
            return call_user_func_array(array($this, 'check'), $args);
        }
        
        /**
         * Check whether a value is allowed or not before pushing
         * 
         * NOTE: Pushing must be implemented by child object
         *       This only checks, doesn't actually push
         * 
         * @return boolean Allowed or not?
         * 
         */ 
        public function pushReference(&$a)
        {
            return $this->check($a);
        }
        
    }
    
}
