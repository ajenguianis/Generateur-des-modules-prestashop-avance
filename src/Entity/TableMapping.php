<?php

namespace App\Entity;

use App\Repository\TableMappingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TableMappingRepository::class)
 */
class TableMapping
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $tableName;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasShopTable;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasLangTable;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function getHasShopTable(): ?bool
    {
        return $this->hasShopTable;
    }

    public function setHasShopTable(bool $hasShopTable): self
    {
        $this->hasShopTable = $hasShopTable;

        return $this;
    }

    public function getHasLangTable(): ?bool
    {
        return $this->hasLangTable;
    }

    public function setHasLangTable(bool $hasLangTable): self
    {
        $this->hasLangTable = $hasLangTable;

        return $this;
    }
}
