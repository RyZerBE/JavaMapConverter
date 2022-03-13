<?php

namespace javamapconverter\head;

class Head {

    /** @var string */
    private $uuid;
    /** @var string */
    private $skinId;
    /** @var string */
    private $name;

    /**
     * Head constructor.
     * @param string $uuid
     * @param string $skinId
     * @param string $name
     */
    public function __construct(string $uuid, string $skinId, string $name = "Custom Head"){
        $this->uuid = $uuid;
        $this->name = $name;
        $this->skinId = $skinId;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUUID(): string{
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getSkinId(): string{
        return $this->skinId;
    }
}