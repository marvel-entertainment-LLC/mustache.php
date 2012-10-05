<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mustache Template rendering Context.
 */
class Mustache_Context
{
    private $stack = array();

    /**
     * Mustache rendering Context constructor.
     *
     * @param mixed $context Default rendering context (default: null)
     */
    public function __construct($context = null)
    {
        if ($context !== null) {
            $this->stack = array($context);
        }
    }

    /**
     * Push a new Context frame onto the stack.
     *
     * @param mixed $value Object or array to use for context
     */
    public function push($value)
    {
        array_push($this->stack, $value);
    }

    /**
     * Pop the last Context frame from the stack.
     *
     * @return mixed Last Context frame (object or array)
     */
    public function pop()
    {
        return array_pop($this->stack);
    }

    /**
     * Get the last Context frame.
     *
     * @return mixed Last Context frame (object or array)
     */
    public function last()
    {
        return end($this->stack);
    }

    /**
     * Find a variable in the Context stack.
     *
     * Starting with the last Context frame (the context of the innermost section), and working back to the top-level
     * rendering context, look for a variable with the given name:
     *
     *  * If the Context frame is an associative array which contains the key $id, returns the value of that element.
     *  * If the Context frame is an object, this will check first for a public method, then a public property named
     *    $id. Failing both of these, it will try `__isset` and `__get` magic methods.
     *  * If a value named $id is not found in any Context frame, returns an empty string.
     *
     * @param string $id Variable name
     *
     * @return mixed Variable value, or '' if not found
     */
    public function find($id)
    {
        return $this->findVariableInStack($id, $this->stack);
    }

    /**
     * Find a 'dot notation' variable in the Context stack.
     *
     * Note that dot notation traversal bubbles through scope differently than the regular find method. After finding
     * the initial chunk of the dotted name, each subsequent chunk is searched for only within the value of the previous
     * result. For example, given the following context stack:
     *
     *     $data = array(
     *         'name' => 'Fred',
     *         'child' => array(
     *             'name' => 'Bob'
     *         ),
     *         'getFathersName' =>
     *              function($child) { return $child->father->name; },
     *     );
     *
     * ... and the Mustache following template:
     *
     *     {{ child.name }}
     *
     * ... the `name` value is only searched for within the `child` value of the global Context, not within parent
     * Context frames.
     *
     * NOTE: method now supports parameter passing syntax in the case the context resolves into
     *       a method call. Parameters are resolved by utilizing the entire context.
     *       Using the following syntax:
     *
     *     {{ child.getFathersName(child) }}
     *
     * @param string $id Dotted variable selector
     *
     * @return mixed Variable value, or '' if not found
     */
    public function findDot($id)
    {
        preg_match_all('/(\w+)(?:\(([.,\w]+)\))?/', $id, $match);
        // match[1] has id, match[2] has params as CSV
        $first  = array_shift($match[1]);
        $params = array_shift($match[2]);
        $chunks = $match[1];
        $value  = $this->findVariableInStack($first, $this->stack, $this->params($params));

        foreach ($chunks as $chunkKey => $chunk) {
            if ($value === '') {
                return $value;
            }

            $value = $this->findVariableInStack($chunk, array($value), $this->params($match[2][$chunkKey]));
        }

        return $value;
    }

    /**
    * Traverse a CSV of function parameters to resolve the values
    * from the context stack. It pulls together the parameters into an array to be
    * ingested by the associated context in findVariableInStack
    *
    * @param  string $str CSV list of parameters to resolve from the context
    *
    * @return array  returns numerical indexed array of resolved parameters
    */
    private function params($str) {
        $params = array();
        $plist  = explode(',', $str);
        //var_dump("str = $str","PLIST", $plist);
        if (empty($plist)) return $params;
        foreach ($plist as $id) {
            if (empty($id)) continue;
            $findFunc = strpos($id,'.')!==FALSE ? 'findDot' : 'find';
            $params[] = $this->$findFunc($id);
        }
        return $params;
    }

    /**
     * Helper function to find a variable in the Context stack.
     *
     * @see Mustache_Context::find
     *
     * @param string $id    Variable name
     * @param array  $stack Context stack
     * @param array  $args  function parameters to pass incase of method context
     *
     * @return mixed Variable value, or '' if not found
     */
    private function findVariableInStack($id, array $stack, $args=array())
    {
        for ($i = count($stack) - 1; $i >= 0; $i--) {
            if (is_object($stack[$i])) {
                if (method_exists($stack[$i], $id)) {
                    return call_user_func_array(array($stack[$i],$id), $args);
                } elseif (isset($stack[$i]->$id)) {
                    return $stack[$i]->$id;
                }
            } elseif (is_array($stack[$i]) && array_key_exists($id, $stack[$i])) {
                return $stack[$i][$id];
            }
        }

        return '';
    }
}
