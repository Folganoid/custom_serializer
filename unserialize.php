<?php

function unserializeCustom($data)
{
    return _unserialize(($data . ''), 0)[2];
}

function utf8Overhead($str)
{
    $s = strlen($str);
    for ($i = strlen($str) - 1; $i >= 0; $i--) {
        $code = utf8_char_code_at($str, $i);
        if ($code > 0x7f && $code <= 0x7ff) {
            $s++;
        } else if ($code > 0x7ff && $code <= 0xffff) {
            $s += 2;
        }
        // trail surrogate
        if ($code >= 0xDC00 && $code <= 0xDFFF) {
            $i--;
        }
    }
    return $s - 1;
}

function utf8_char_code_at($str, $index = 0)
{
    $char = mb_substr($str, $index, 1, 'UTF-8');

    if (mb_check_encoding($char, 'UTF-8')) {
        $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
        return hexdec(bin2hex($ret));
    } else {
        return null;
    }
}

function readUntil($data, $offset, $stopchr)
{
    $i = 2;
    $buf = [];
    $chr = substr($data, $offset, 1);

    while ($chr != $stopchr) {

        if (($i + $offset) > strlen($data)) {
            return false;
        }
        $buf[] = $chr;
        $chr = substr($data, $offset + ($i - 1), 1);
        $i++;
    }
    return [count($buf), implode('', $buf)];
}

function readChrs($data, $offset, $length)
{
    $buf = [];
    for ($i = 0; $i < $length; $i++) {
        $chr = substr($data, $offset + ($i - 1), 1);
        $buf[] = $chr;
        $length -= utf8Overhead($chr);
    }
    return [count($buf), implode('', $buf)];
}

function _unserialize($data, $offset)
{

    $typeconvert = function ($x) {
        return $x;
    };

    if (!$offset) {
        $offset = 0;
    }

    $dtype = substr($data, $offset, 1);
    $dtype = strtolower($dtype);

    $dataoffset = +$offset + 2;

    switch ($dtype) {
        case 'i':
            $typeconvert = function ($x) {
                return intval($x, 10);
            };
            $readData = readUntil($data, $dataoffset, ';');
            $chrs = $readData[0];
            $readdata = $readData[1];
            $dataoffset += $chrs + 1;
            break;
        case 'b':
            $typeconvert = function ($x) {
                return (intval($x, 10) !== 0);
            };
            $readData = readUntil($data, $dataoffset, ';');
            $chrs = $readData[0];
            $readdata = $readData[1];
            $dataoffset += $chrs + 1;
            break;
        case 'd':
            $typeconvert = function ($x) {
                return floatval($x);
            };
            $readData = readUntil($data, $dataoffset, ';');
            $chrs = $readData[0];
            $readdata = $readData[1];
            $dataoffset += $chrs + 1;
            break;
        case 'n':
            $readdata = null;
            break;
        case 's':
            $ccount = readUntil($data, $dataoffset, ':');
            $chrs = $ccount[0];
            $stringlength = $ccount[1];
            $dataoffset += $chrs + 2;
            $readData = readChrs($data, $dataoffset + 1, intval($stringlength, 10));
            $chrs = $readData[0];
            $readdata = $readData[1];
            $dataoffset += $chrs + 2;
            if ($chrs !== intval($stringlength, 10) && $chrs !== strlen($readdata)) {
                return false;
            }
            break;
        case 'a':
            $readdata = [];

            $keyandchrs = readUntil($data, $dataoffset, ':');
            $chrs = $keyandchrs[0];
            $keys = $keyandchrs[1];
            $dataoffset += $chrs + 2;

            $length = intval($keys, 10);
            $contig = true;

            for ($i = 0; $i < $length; $i++) {
                $kprops = _unserialize($data, $dataoffset);
          $kchrs = $kprops[1];
          $key = $kprops[2];
          $dataoffset += $kchrs;

          $vprops = _unserialize($data, $dataoffset);
          $vchrs = $vprops[1];
          $value = $vprops[2];
          $dataoffset += $vchrs;

          if ($key !== $i) {
              $contig = false;
          }

          $readdata[$key] = $value;
        }

            if ($contig) {
                $array = [];
          for ($i = 0; $i < $length; $i++) {
              $array[$i] = $readdata[$i];
          }
          $readdata = $array;
        }

            $dataoffset += 1;
        break;
      default:
          return false;
        break;
    }
    return [$dtype, $dataoffset - $offset, $typeconvert($readdata)];
  }

$aa = unserializeCustom('a:2:{s:8:"all125k9";i:2;s:5:"vlllk";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;s:3:"vvv";d:24.55;}}');

var_dump(json_encode($aa));
