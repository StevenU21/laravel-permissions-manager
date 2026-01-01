<?php

namespace Deifhelt\LaravelPermissionsManager\Tests\Unit;

use Deifhelt\LaravelPermissionsManager\PermissionTranslator;
use Deifhelt\LaravelPermissionsManager\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PermissionTranslatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock translator
        app('translator')->addLines([
            'permissions.special.assign permissions' => 'Asignar permisos',

            'permissions.actions.create' => 'Crear',
            'permissions.actions.read' => 'Ver',

            'permissions.resources.users' => 'Usuarios',
            'permissions.resources.inventory_movements' => 'Movimientos de inventario',

            'permissions.dictionary.old_resource' => 'Recurso Viejo',
            'permissions.dictionary.products' => 'Productos',
        ], 'es', 'permissions');

        app()->setLocale('es');
    }

    #[Test]
    public function it_translates_simple_composed_permission()
    {
        $this->assertEquals('Crear Usuarios', PermissionTranslator::translate('create users'));
    }

    #[Test]
    public function it_translates_special_permission_override()
    {
        $this->assertEquals('Asignar permisos', PermissionTranslator::translate('assign permissions'));
    }

    #[Test]
    public function it_falls_back_to_token_translation_if_resource_not_defined()
    {
        app('translator')->addLines(['permissions.dictionary.products' => 'Productos'], 'es', 'permissions');

        $this->assertEquals('Crear Productos', PermissionTranslator::translate('create products'));
    }

    #[Test]
    public function it_handles_complex_resource_names_defined_explicitly()
    {
        $this->assertEquals('Ver Movimientos de inventario', PermissionTranslator::translate('read inventory_movements'));
    }

    #[Test]
    public function it_handles_complex_resource_names_via_tokens_fallback()
    {
        $this->assertEquals('Crear Recurso Viejo', PermissionTranslator::translate('create old_resource'));
    }
}
