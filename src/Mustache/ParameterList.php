<?php
/**
 * /*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Function parameter parser helper class.
 *
 * This class is responsible itemizing into an array CSV of function
 * parameters found in a mustache template.
 * @author Eddy Nunez <enunez@marvel.com>
 */
class Mustache_ParameterList
{
    /**
     * PHP open tag
     * @var string
     */
    const TOKEN_OPEN_TAG = '<?php ';

    /**
     * array(begin_token, end_token) that defines a func parameter
     * null end_token mean no end token required
     * @array
     */
    public static $tokenStartEndMap = array(
        array(T_ARRAY, '\)'), // arrays
        array(T_STRING, '[,\)]'), // string var references
        array(T_CONSTANT_ENCAPSED_STRING, null), // string literal
        array(T_DNUMBER, null), // decimal numbers
        array(T_LNUMBER, null), // regular numbers
    );

    /**
     * Parse string containing function parameters utilizing the builtin
     * Zend lexical parser, which works with tokens, real cool!
     * The method just separates each parameter string into individual
     * elements in an array.
     * 
     * @param  string   $parameterString    CSV list of function parameters.
     * @return array
     */
    public static function explode($parameterString)
    {
        $str    = self::TOKEN_OPEN_TAG . $parameterString;
        $arr    = token_get_all($str);
        $tok    = current($arr);
        $params = array();
        while ($tok)
        {
            list($id,$text) = is_string($tok) ? array(null,$tok) : $tok;
            foreach (self::$tokenStartEndMap as $startEnd)
            {
                list($start,$end) = $startEnd;
                if ($start == $id)
                    $params[] = self::compileParam($end, $arr);
            }
            $tok = next($arr);
        }
        return $params;
    }

    /**
     * Helper method to compile all the tokens that make up
     * an individual parameter. The only thing method needs is the
     * current list of tokens and the token regex that ends the
     * parameter token sequence.  Method supports parenthesis nesting.
     * 
     * @param  string   $endToken   Token regex used to mark end of parameter.
     * @param  array    $tokens     Reference to whole token list, advances pointer.
     * @return string   compiled parameter tokens.
     */
    private static function compileParam($endToken, array &$tokens){
        $tok     = current($tokens);
        $param   = ''; 
        $nesting = 0;
        do {
            list($id,$text) = is_string($tok) ? array(null,$tok) : $tok;
            if ($text == '(') ++$nesting;
            if ($text == ')') --$nesting;
            $param .= $text;

            $atEnd = (bool) preg_match("/$endToken/",$text);
            if ($nesting || (!empty($endToken) && !$atEnd))
                $tok = next($tokens);
        } while (
            $tok && $endToken && ($nesting > 0 || !$atEnd)
        );
        $param = rtrim($param, ',');
        return $param;
    }
}