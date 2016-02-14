<?php
namespace Entities;

/**
 * @Entity
 */

class User {
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string", length=24, nullable=true) */
    public $username;

    /** @Column(type="string", length=255, nullable=true) */
    public $password;

    /** @Column(type="string", length=100, nullable=true) */
    public $email;

    /** @Column(type="boolean", nullable=true) */
    public $is_admin;

    /** @Column(type="boolean", nullable=true) */
    public $is_banned;

    /** @Column(type="integer", nullable=true) */
    public $num_uploaded_mods;
}