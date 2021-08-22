<?php

namespace Common\Exception;

class YNABTransformerException extends \RuntimeException
{
    private $details;
    private $causes;

    const ASSERTION_FAILED = 1011;

    public static function createWithDetails($details): YNABTransformerException
    {
        $e = new static();
        $e->setDetails($details);

        return $e;
    }

    public static function causedBy($errors): YNABTransformerException
    {
        $e = new static();
        $e->setCauses($errors);

        return $e;
    }

    public function toArray(): array
    {
        $a['error']['code'] = $this->getCode();
        $a['error']['message'] = $this->getMessage();
        $a['error']['details'] = $this->getDetails();
        $a['error']['caused_by'] = $this->getCauses();

        return $a;
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function setDetails($details)
    {
        $this->details = $details;
    }

    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param mixed $causes
     */
    public function setCauses($causes): void
    {
        $this->causes = $causes;
    }

    /**
     * @return mixed
     */
    public function getCauses()
    {
        return $this->causes;
    }
}