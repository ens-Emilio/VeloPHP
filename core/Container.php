<?php

declare(strict_types=1);

namespace Core;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;

class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $singletons = [];

    /**
     * Registra um binding no container.
     */
    public function bind(string $abstract, callable|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Registra um singleton — instanciado uma única vez e reutilizado.
     */
    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'singleton' => true,
        ];
    }

    /**
     * Registra uma instância já existente como singleton.
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->singletons[$abstract] = $instance;
    }

    /**
     * Verifica se existe um binding ou singleton registrado.
     */
    public function has(string $abstract): bool
    {
        return isset($this->singletons[$abstract]) || isset($this->bindings[$abstract]);
    }

    /**
     * PSR-11: Finds an entry of the container by its identifier and returns it.
     */
    public function get(string $id): object
    {
        if (!$this->has($id)) {
            throw new Exceptions\ContainerNotFoundException("Nenhum binding encontrado para [{$id}].");
        }

        return $this->make($id);
    }

    /**
     * Resolve uma classe ou binding do container.
     */
    public function make(string $abstract): object
    {
        // Retorna singleton se já instanciado
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        $binding = $this->bindings[$abstract] ?? null;

        // Singleton registrado mas ainda não instanciado
        if (is_array($binding) && ($binding['singleton'] ?? false)) {
            $concrete = $binding['concrete'];
            $instance = $this->build($concrete);
            $this->singletons[$abstract] = $instance;
            return $instance;
        }

        // Binding como callable (factory)
        if (is_callable($binding)) {
            return $binding($this);
        }

        // Binding como string (nome de classe)
        if (is_string($binding)) {
            return $this->build($binding);
        }

        // Auto-resolução: tenta construir a classe diretamente
        return $this->build($abstract);
    }

    /**
     * Instancia uma classe resolvendo automaticamente as dependências do construtor.
     */
    private function build(callable|string $concrete): object
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("A classe [{$concrete}] não pode ser instanciada.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = array_map(
            fn (ReflectionParameter $param) => $this->resolveDependency($param),
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve um parâmetro do construtor via type-hint ou valor padrão.
     */
    private function resolveDependency(ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new \RuntimeException(
            "Não foi possível resolver o parâmetro [{$param->getName()}] na classe [{$param->getDeclaringClass()->getName()}]."
        );
    }
}
