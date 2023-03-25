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

namespace phpDocumentor\Transformer\Writer;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use phpDocumentor\Descriptor\ApiSetDescriptor;
use phpDocumentor\Descriptor\Collection as DescriptorCollection;
use phpDocumentor\Descriptor\DocumentationSetDescriptor;
use phpDocumentor\Descriptor\FileDescriptor;
use phpDocumentor\Descriptor\ProjectDescriptor;
use phpDocumentor\Faker\Faker;
use phpDocumentor\Transformer\Transformation;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \phpDocumentor\Transformer\Writer\Sourcecode
 * @covers ::__construct
 * @covers ::<private>
 */
final class SourcecodeTest extends MockeryTestCase
{
    use Faker;
    use ProphecyTrait;

    /** @var Graph */
    private $sourceCode;

    protected function setUp(): void
    {
        $pathGenerator = $this->prophesize(PathGenerator::class);
        $pathGenerator->generate(
            Argument::type(FileDescriptor::class),
            Argument::type(Transformation::class)
        )->willReturn((string) $this->faker()->path());
        $this->sourceCode = new Sourcecode(
            $pathGenerator->reveal()
        );
    }

    /**
     * @covers ::transform
     */
    public function testNoInteractionWithTransformationWhenSourceShouldNotIncluded(): void
    {
        $transformation = $this->prophesize(Transformation::class);
        $transformation->template()->shouldNotBeCalled();

        $apiSet = $this->faker()->apiSetDescriptor();
        $projectDescriptor = $this->giveProjectDescriptor($apiSet);

        $this->sourceCode->transform($transformation->reveal(), $projectDescriptor, $apiSet);
    }

    /**
     * @covers ::transform
     */
    public function testNoInteractionWithTransformationWhenSourceIsIncluded(): void
    {
        $transformation = $this->prophesize(Transformation::class);
        $transformation->template()->shouldBeCalled()->willReturn($this->faker()->template());

        $api = $this->faker()->apiSetDescriptor();
        $api->getSettings()['include-source'] = true;
        $projectDescriptor = $this->giveProjectDescriptor($api);

        $this->sourceCode->transform($transformation->reveal(), $projectDescriptor, $api);
    }

    private function giveProjectDescriptor(ApiSetDescriptor $apiDescriptor): ProjectDescriptor
    {
        $projectDescriptor = $this->faker()->projectDescriptor();
        $versionDescriptor = $this->faker()->versionDescriptor([$apiDescriptor]);
        $projectDescriptor->getVersions()->add($versionDescriptor);
        $apiDescriptor->setFiles(
            DescriptorCollection::fromClassString(
                DocumentationSetDescriptor::class,
                [$this->faker()->fileDescriptor()]
            )
        );

        return $projectDescriptor;
    }
}
