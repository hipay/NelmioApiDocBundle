<?php

namespace Nelmio\ApiDocBundle\Util;

class DocCommentExtractor
{
    /**
     * @param  \Reflector $reflected
     * @return string
     */
    public function getDocComment(\Reflector $reflected)
    {
        $comment = $reflected->getDocComment();

        // let's clean the doc block
        $comment = str_replace('/**', '', $comment);
        $comment = str_replace('*', '', $comment);
        $comment = str_replace('*/', '', $comment);
        $comment = str_replace("\r", '', trim($comment));
        $comment = preg_replace("#^\n[ \t]+[*]?#i", "\n", trim($comment));
        $comment = preg_replace("#[\t ]+#i", ' ', trim($comment));
        $comment = str_replace("\"", "\\\"", $comment);

        return $comment;
    }

    /**
     * @param  \Reflector $reflected
     * @return string
     */
    public function getDocCommentText(\Reflector $reflected)
    {
        $comment = $reflected->getDocComment();

        // Remove PHPDoc
        $comment = preg_replace('/^\s+\* @[\w0-9]+.*/msi', '', $comment);

        // let's clean the doc block
        $comment = str_replace('/**', '', $comment);
        $comment = str_replace('*/', '', $comment);
        $comment = preg_replace('/^\s*\* ?/m', '', $comment);

        return trim($comment);
    }

    /**
     * @param  \Reflector $reflected
     * @return string
     */
    public function getDocCommentTextFromClass(\Reflector $reflected) {
        $comment = $this->getDocCommentText($reflected);

        $classPath = explode('\\', $reflected->name);
        $comment = preg_replace('/^Class '.end($classPath).'(.*)?([\n|\r])?/m', '', $comment);

        return trim($comment);
    }

    private function getExtrasProperties($props, $is_custom = false) {
        $extract = array();
        $arr = array_filter(preg_split("/,[\n|\r]/", $props), function($e) {
            return preg_match("/\w+/", $e);
        });

        if($is_custom) {
            foreach($arr as $el) {
                $info = explode('=', $el);
                $value = trim(preg_replace('/"/', '', $info[1]));
                if(preg_match("/^\d+$/", $value)) {
                    $value = floatval($value);
                }
                $extract["x-".str_replace('"', '', trim($info[0]))] = $value;
            }
        }
        else {
            foreach($arr as $el) {
                $info = explode('=', $el);
                $extract[trim($info[0])] = trim(preg_replace('/"/', '', $info[1]));
            }
        }

        return $extract;
    }

    /**
     * @param \Reflector $reflected
     * @return string
     */
    public function getDocCommentExtras(\Reflector $reflected) {
        $comment = $reflected->getDocComment();
        preg_match("/[\t| ]*ApiDoc\((.*(?!\)))[\t| ]*\)/s", $comment, $docApi);
        
        if($docApi != null) {
            $extract = array();
            $apiDocProps = array();
            $props = str_replace('*', '', $docApi[1]);

            preg_match("/[\t| ]*x=\{(.*)[\t| ]*\}/s", $props, $customProp);
            if($customProp != null) {
                $props = str_replace($customProp[0], '', $props);
                $extract = $this->getExtrasProperties($customProp[1], true);
            }
            $props = str_replace('*', '', $props);

            $extract = array_merge($extract, $this->getExtrasProperties($props));

            ksort($extract);
        }
        else
            $extract = null;

        return $extract;
    }

}
