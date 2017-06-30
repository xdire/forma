<?php namespace Xdire\Forma\Models\ORM;

/**
 * Anton Repin <ar@xdire.io>
 * Date: 3/4/17
 */

use Xdire\Forma\SchemaEntities\ORMTypeEntity;

class ORMModel
{

    /**
     *  List of available Keywords
     *  ---------------------------------------------------------------------
     *  @var int[]
     */
    private static $keywords = [
        "@type" => 100,
        "@primary" => 101,
        "@secondary" => 102,
        "@column" => 103,
        "@default" => 104,
        "@table" => 105,
        "@auto" => 106,
        "@readonly" => 107,
        "@ignore" => 108,
        "@relation" => 109,
        "@dependant" => 110
    ];

    /**
     *  Will produce default type for untyped parameter
     *  ---------------------------------------------------------------------
     *  @param  string $name
     *  @return ORMTypeEntity
     */
    protected static function __getDefaultTypeForName($name) {

        $typeEntity = new ORMTypeEntity();

        $typeEntity->column = $name;
        $typeEntity->type = ORMTypes::T_STR | ORMTypes::T_NULL;
        $typeEntity->withDefault = true;
        $typeEntity->parameterName = $name;

        return $typeEntity;

    }

    /**
     *  Will produce ORMTypeEntity for assigned Type description of variable
     *  ---------------------------------------------------------------------
     *  @param $textBlock
     *  @return ORMTypeEntity|null
     */
    protected static function __getTypeFromTextAnnotation($textBlock) {

        $typeEntity = new ORMTypeEntity();
        $found = 0;

        /*
         *  Document commentary is always minimum of 5 symbols
         */
        if(!isset($textBlock[4]))
            return null;

        /*
         *  Go through Commentary
         */
        for($i = 0; $i < 9999; $i++) {

            /*
             *  Start seeking for type or meta header
             */
            if($textBlock[$i] === '@') {

                $found += self::parseType($textBlock, $i, $typeEntity);

            }
            /*
             *  If it is end block -> break cycle and return null
             */
            elseif ($textBlock[$i] === '/') {

                if($i > 0 && $textBlock[$i - 1] === '*')
                    break;

            }

        }

        return $found > 0 ? $typeEntity : null;

    }

    /**
     *  Will parse type found in the string and assign it values to attached ORMTypeEntity
     *  ---------------------------------------------------------------------
     *  @param string        $textBlock
     *  @param int           $start
     *  @param ORMTypeEntity $type
     *  @return int|null
     */
    private static function parseType(&$textBlock, &$start, ORMTypeEntity &$type) {

        $typeNum = 0;

        $currentKeyword = null;

        $stringOpened = false;

        $strTypes = [0 => null, 1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null];

        $i = $start;
        /*
         *  Set the limit of sub string which should be taken into parsing
         */
        $max = $start + 128;

        for(; $i < $max; $i++) {

            $chr = $textBlock[$i];

            /*
             *  No forward slashes inside a type declaration
             */
            if($chr === '/') {

                break;

            }
            /*
             *  Check what will be happen on comment brake with space, "or", new line
             */
            else if (!$stringOpened && ($chr === ' ' || $chr === '|' || $chr === "\n")) {

                $readValue = isset($strTypes[$typeNum]) ? rtrim($strTypes[$typeNum]) : null;

                /*
                 *  @primary or @secondary modifiers
                 */
                if(isset(self::$keywords[$readValue])) {

                    $currentKeyword = self::$keywords[$readValue];

                    /*
                     *  @primary modifier
                     */
                    if($currentKeyword === 101) {

                        $type->primary = true;

                    }
                    /*
                     *  @secondary modifier
                     */
                    elseif ($currentKeyword === 102) {

                        $type->secondary = true;

                    }
                    /*
                     *  @auto modifier
                     */
                    elseif ($currentKeyword === 106) {

                        $type->auto = true;

                    }
                    /*
                    *  @readonly modifier
                    */
                    elseif ($currentKeyword === 107) {

                        $type->readOnly = true;

                    }
                    /*
                     *  @ignore modifier
                     */
                    elseif ($currentKeyword === 108) {

                        $type->ignore = true;

                    }
                    /*
                     *  @dependant modifier
                     */
                    elseif ($currentKeyword === 110) {

                        $type->withDependant = true;

                    }

                }
                /*
                 *  @type modifier
                 */
                elseif ($currentKeyword === 100) {

                    if($ORMType = ORMTypes::getRealTypeForString($readValue))
                        $type->type = $type->type | $ORMType;

                }
                /*
                 *  @column modifier
                 */
                elseif ($currentKeyword === 103) {

                    $type->column = $readValue;

                }
                /*
                 *  @default modifier
                 */
                elseif ($currentKeyword === 104) {

                    $type->withDefault = true;
                    $type->default = $readValue;

                }
                /*
                 *  @table modifier
                 */
                elseif ($currentKeyword === 105) {

                    $type->table = $readValue;

                }
                /*
                 *  @relation modifier
                 */
                elseif ($currentKeyword === 109) {

                    if ($type->withRelationType === 0) {

                        if($readValue === "one")
                            $type->withRelationType = 1;

                        elseif ($readValue === "many")
                            $type->withRelationType = 2;

                    } else if ($type->withRelationType > 0 && $type->withRelation === null) {

                        $type->withDependant = true;
                        $type->withRelation .= $readValue;

                    }

                }

                $typeNum++;

                /*
                 *  Return to the type block parsing on a new line
                 */
                if($chr === "\n")
                    break;

                continue;

            }
            /*
             *  If our type value was quoted start to pull it as full string
             */
            else if($chr === "\"") {

                $stringOpened = !$stringOpened;

            }
            /*
             *  Populate current accumulator
             */
            else
                $strTypes[$typeNum] .= $chr;

        }

        $start = $i;
        return $typeNum;

    }

    /**
     *  Will create Int array where every element forcefully casted to proper format
     *  ---------------------------------------------------------------------
     *  @param   int[] $integerArray
     *  @return  int[]
     */
    public static function prepareIntegerArray(array $integerArray) {
        $a = [];

        foreach ($integerArray as $int)
            $a[] = (int)$int;

        return $a;
    }

    /**
     *  Will create Float array where every element forcefully casted to proper format
     *  ---------------------------------------------------------------------
     *  @param   float[] $floatArray
     *  @return  float[]
     */
    public static function prepareFloatArray(array $floatArray) {
        $a = [];

        foreach ($floatArray as $float)
            $a[] = (float)$float;

        return $a;
    }

    /**
     *  Will create String array where every element having single quotes around it
     *  ---------------------------------------------------------------------
     *  @param   string[] $stringArray
     *  @return  string[]
     */
    public static function prepareStringArray(array $stringArray) {
        $a = [];

        foreach ($stringArray as $str)
            $a[] = "'".static::prepareString($str)."'";

        return $a;
    }

    /**
     *  Will escape string by back-slashing following symbols:
     *  ---------------------------------------------------------------------
     *  \
     *  '
     *  "
     *
     *  @param $string
     *  @return string
     */
    public static function prepareString($string) {

        $l = mb_strlen($string);
        $n = "";

        for($i=0; $i < $l; $i++) {

            $a = $string[$i];

            switch ($a) {
                case '\\':
                    $n .= '\\\\';
                    break;
                case '\'':
                    $n .= '\\\'';
                    break;
                case '"':
                    $n .= '\"';
                    break;
                default:
                    $n .= $a;
            }

        }

        return $n;

    }

}