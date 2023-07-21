<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace phpDocumentor\Descriptor;

use phpDocumentor\Descriptor\Interfaces\ClassInterface;
use phpDocumentor\Descriptor\Interfaces\ElementInterface;
use phpDocumentor\Descriptor\Interfaces\FileInterface;
use phpDocumentor\Descriptor\Interfaces\InterfaceInterface;
use phpDocumentor\Descriptor\Interfaces\PropertyInterface;
use phpDocumentor\Descriptor\Interfaces\TraitInterface;
use phpDocumentor\Descriptor\Tag\VarDescriptor;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use Webmozart\Assert\Assert;

/**
 * Descriptor representing a property.
 *
 * @api
 * @package phpDocumentor\AST
 */
class PropertyDescriptor extends DescriptorAbstract implements
    Interfaces\PropertyInterface,
    Interfaces\VisibilityInterface
{
    use Traits\HasVisibility;
    use Traits\CanHaveAType;
    use Traits\CanHaveADefaultValue;

    /** @var ClassInterface|TraitInterface|null $parent */
    protected ?ElementInterface $parent = null;

    protected bool $static = false;
    private bool $readOnly = false;
    private bool $writeOnly = false;

    /**
     * {@inheritDoc}
     */
    public function setParent($parent): void
    {
        /** @var ClassInterface|TraitInterface $parent */
        Assert::isInstanceOfAny($parent, [ClassInterface::class, TraitInterface::class]);

        $this->setFullyQualifiedStructuralElementName(
            new Fqsen($parent->getFullyQualifiedStructuralElementName() . '::$' . $this->getName()),
        );

        $this->parent = $parent;
    }

    /**
     * @return ClassInterface|TraitInterface|null
     */
    public function getParent(): ?ElementInterface
    {
        return $this->parent;
    }

    public function setStatic(bool $static): void
    {
        $this->static = $static;
    }

    public function isStatic(): bool
    {
        return $this->static;
    }

    public function getType(): ?Type
    {
        if ($this->type === null) {
            /** @var VarDescriptor|bool $var */
            $var = $this->getVar()->getIterator()->current();
            if ($var instanceof VarDescriptor) {
                return $var->getType();
            }
        }

        return $this->type;
    }

    /**
     * @return Collection<VarDescriptor>
     */
    public function getVar(): Collection
    {
        /** @var Collection<VarDescriptor> $var */
        $var = $this->getTags()->fetch('var', new Collection());
        if ($var->count() !== 0) {
            return $var;
        }

        $inheritedElement = $this->getInheritedElement();
        if ($inheritedElement) {
            return $inheritedElement->getVar();
        }

        return new Collection();
    }

    /**
     * Returns the file associated with the parent class or trait.
     */
    public function getFile(): FileInterface
    {
        $file = $this->getParent()->getFile();

        Assert::notNull($file);

        return $file;
    }

    /**
     * Returns the property from which this one should inherit, if any.
     */
    public function getInheritedElement(): ?PropertyInterface
    {
        /** @var ClassInterface|InterfaceInterface|null $associatedClass */
        $associatedClass = $this->getParent();

        if (
            ($associatedClass instanceof ClassInterface || $associatedClass instanceof InterfaceInterface)
            && ($associatedClass->getParent() instanceof ClassInterface
                || $associatedClass->getParent() instanceof InterfaceInterface
            )
        ) {
            /** @var ClassInterface|InterfaceInterface $parentClass */
            $parentClass = $associatedClass->getParent();

            return $parentClass->getProperties()->fetch($this->getName());
        }

        return null;
    }

    public function setReadOnly(bool $value): void
    {
        $this->readOnly = $value;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function setWriteOnly(bool $value): void
    {
        $this->writeOnly = $value;
    }

    public function isWriteOnly(): bool
    {
        return $this->writeOnly;
    }
}
