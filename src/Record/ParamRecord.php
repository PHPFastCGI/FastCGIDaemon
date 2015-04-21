<?php

namespace PHPFastCGI\FastCGIDaemon\Record;

class ParamRecord extends Record
{
    protected $name;

    protected $value;

    public function __construct(RecordHeader $header, $content)
    {
        if ($header->getContentLength() < 1) {
            $this->name = $this->value = null;
        } else {
            $structure = $this->getStructure($content);

            $format = (
                'C' . $structure['offset']      . 'dummy/' .
                'a' . $structure['nameLength']  . 'name/' .
                'a' . $structure['valueLength'] . 'value/'
            );

            $parameters = unpack($format, $content);

            $this->name  = $parameters['name'];
            $this->value = $parameters['value'];
        }

        parent::__construct($header, $content);
    }

    private function getStructure($content)
    {
        $initialBytes = unpack('C5', $content);

        $extendedLengthName  = $initialBytes[1] & 0x80;
        $extendedLengthValue = $extendedLengthName ?
            $initialBytes[2] & 0x80 : $initialBytes[5] & 0x80;

        $format = (
            ($extendedLengthName  ? 'N' : 'C') . 'nameLength/' .
            ($extendedLengthValue ? 'N' : 'C') . 'valueLength'
        );

        $structure = unpack($format, $content);
        $structure['offset'] = ($extendedLengthName ? 4 : 1) +
            ($extendedLengthValue ? 4 : 1);

        return $structure;
    }

    public function isEndRecord()
    {
        return ($this->name === null) && ($this->value === null);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }
}
