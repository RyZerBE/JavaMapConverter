<?php

namespace javamapconverter\head;

class HeadsCategory {

    /** @var string */
    private $name;

    /** @var array  */
    private $heads = [];

    /**
     * HeadsCategory constructor.
     * @param string $name
     */
    public function __construct(string $name){
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return Head[]
     */
    public function getHeads(): array{
        return $this->heads;
    }

    /**
     * @param Head $head
     */
    public function addHead(Head $head): void {
        $this->heads[$head->getUUID()] = $head;
    }

    /**
     * @param string $uuid
     * @return Head|null
     */
    public function getHead(string $uuid): ?Head {
        return $this->heads[$uuid] ?? null;
    }
}