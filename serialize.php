<?php

function serializeCustom ($mixedValue) {

  $ktype = '';
  $vals = '';
  $count = 0;

  $type = _getType($mixedValue);

  switch ($type) {
      case false:
      break;
    case 'boolean':
        $val = 'b:' . ($mixedValue ? '1' : '0');
      break;
    case 'number':
        $val = 'i:' . $mixedValue;
      break;
    case 'float':
        $val = 'd:' . $mixedValue;
      break;
    case 'string':
        $val = 's:' . _utf8Size($mixedValue) . ':"' . $mixedValue . '"';
      break;
    case 'array':
    case 'object':
        $val = 'a';
      foreach ($mixedValue as $key => $value) {

          $okey = (preg_match('/^[0-9]+$/', $key) == 1) ? intval($key, 10) : $key;
          $vals .= serializeCustom($okey) . serializeCustom($mixedValue[$key]);
          $count++;
        }
      $val .= ':' . $count . ':{' . $vals . '}';
      break;
    case 'null':
    default:
        $val = 'N';
      break;
  }
  if ($type !== 'object' && $type !== 'array') {
      $val .= ';';
  }

  return $val;
}

function _utf8Size($str) {
    return strlen($str);
  }

function _getType($inp) {

    if (is_array($inp)) {
        if (array() === $inp) return "object";
        if(array_keys($inp) !== range(0, count($inp) - 1)) {
            return "object";
        } else {
            return "array";
        }
    }
    if (is_bool($inp)) return "boolean";
    if (is_null($inp)) return "null";
    if (is_string($inp)) return "string";
    if (is_int($inp)) return "number";
    if (is_numeric($inp)) return "float";
    if (is_object($inp)) return "object";

    return false;
}

$data = [
    17 => "Vliestapeten",
    18 => "Bodenbelä",
    20 => "Bodenbeläge"
];

var_dump(serializeCustom($data));
var_dump(unserialize(serializeCustom($data)));
