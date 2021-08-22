<?php

namespace Common;

use Carbon\Carbon;

class FileNameGenerator
{
    /**
     * @var mixed|string
     */
    private mixed $inputDir;
    /**
     * @var mixed|string
     */
    private mixed $inputBasename;
    /**
     * @var mixed|string
     */
    private mixed $inputExtension;
    /**
     * @var mixed|string
     */
    private mixed $inputFilename;

    private string $suffix = '';
    private string $extension = '';
    private bool $avoidDuplicates = false;

    private function __construct($inputFilename)
    {
        $path_parts = pathinfo($inputFilename);
        $this->inputDir = $path_parts['dirname'];
        $this->inputBasename = $path_parts['basename'];
        $this->inputExtension = $path_parts['extension'];
        $this->inputFilename = $path_parts['filename'];
    }

    public static function fromSourceFilename($filename): FileNameGenerator
    {
        return new self($filename);
    }

    public function generate(): string
    {
        $theName = $this->inputFilename;
        if ($this->suffix != '') {
            $theName .= $this->suffix;
        }
        if ($this->extension != '') {
            $theExtension = $this->extension;
        } else {
            $theExtension = $this->inputExtension;
        }

        $fullName = $this->inputDir.'/'.$theName.'.'.$theExtension;

        if (($this->avoidDuplicates) && (file_exists($fullName))) {
            $theName .= '-'.Carbon::now()->format('Y-m-d_h-i-s');
        }
        $fullName = $this->inputDir.'/'.$theName.'.'.$theExtension;
        return $fullName;
    }

    public function withSuffix($suffix): self
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function avoidDuplicates(): self
    {
        $this->avoidDuplicates = true;
        return $this;
    }

    public function withExtension(string $extension)
    {
        $this->extension = $extension;
        return $this;
    }
}