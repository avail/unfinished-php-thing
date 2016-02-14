<?php
namespace Entities;

/**
 * @Entity
 * @Table(uniqueConstraints={@UniqueConstraint(name="key_unique", columns={"key"})}, indexes={@Index(name="key_index", columns={"key"})})
 */

class Keys {
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string", length=32, nullable=true) */
    public $key;

    /** @Column(type="boolean", nullable=true) */
    public $valid;
}