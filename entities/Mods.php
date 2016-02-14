<?php
namespace Entities;

/**
 * @Entity
 */

class Mods {
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="integer", nullable=true)  */
    public $uploader_id;

    /** @Column(type="string", length=255, nullable=true) */
    public $modname;

    /** @Column(type="text", nullable=true) */
    public $description;
}