<?php
declare(strict_types=1);

namespace NamelessCoder\FluidDocumentationGenerator\Tests\Functional\RstRendering;

use NamelessCoder\FluidDocumentationGenerator\Data\DataFileResolver;
use NamelessCoder\FluidDocumentationGenerator\Export\RstExporter;
use NamelessCoder\FluidDocumentationGenerator\SchemaDocumentationGenerator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class RootIndexFileTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $vfs;

    /**
     * the generated file is compared against this fixture file
     * @var string
     */
    private $fixtureFilePath = __DIR__ . '/../../Fixtures/rendering/output/Documentation/Index.rst';

    /**
     * output of the generation process
     * @var string
     */
    private $generatedFilePath = 'outputDir/public/Index.rst';

    protected function setUp()
    {
        $this->vfs = vfsStream::setup('outputDir');
        $this->vfs->addChild(vfsStream::newDirectory('cache'));
        $dataFileResolver = DataFileResolver::getInstance(vfsStream::url('outputDir'));
        $dataFileResolver->setResourcesDirectory(__DIR__ . '/../../../resources/');
        $dataFileResolver->setSchemasDirectory(__DIR__ . '/../../Fixtures/rendering/input/');
        $schemaDocumentationGenerator = new SchemaDocumentationGenerator(
            [
                new RstExporter()
            ]
        );
        $schemaDocumentationGenerator->generateFilesForRoot();
    }

    /**
     * @test
     */
    public function fileIsCreated()
    {
        $this->assertTrue($this->vfs->hasChild($this->generatedFilePath));
    }

    /**
     * @test
     */
    public function includeClausePointsToSettingsCfg()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        $this->assertSame('.. include:: Includes.txt' . PHP_EOL, $output[0]);
    }

    /**
     * @test
     */
    public function headlineAsExpected()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        // first line is include, then empty, then upper headline decoration, then text -> fourth line
        $index = 3;
        $this->assertSame('Fluid ViewHelper Documentation' . PHP_EOL, $output[$index]);
    }

    /**
     * @test
     */
    public function headlineIsProperlyDecorated()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        // first line is include, then empty, then upper headline decoration, then text, then lower headline decoration
        $headlineTextIndex = 3;
        $lengthOfHeadline = strlen($output[$headlineTextIndex]);
        $this->assertSame($lengthOfHeadline, strlen($output[$headlineTextIndex - 1]));
        $this->assertRegExp('/^[=]+$/', $output[$headlineTextIndex - 1]);
        $this->assertSame($lengthOfHeadline, strlen($output[$headlineTextIndex + 1]));
        $this->assertRegExp('/^[=]+$/', $output[$headlineTextIndex + 1]);
    }

    /**
     * @test
     */
    public function tocTreeContainsSubDirectoriesAsExpected()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        $index = 11;
        $this->assertSame('   typo3/backend/9.4/Index' . PHP_EOL, $output[$index]);
        $this->assertSame('   typo3/backend/9.5/Index' . PHP_EOL, $output[$index + 1]);
    }

    /**
     * @test
     */
    public function generatedFileIsSameAsFixture()
    {
        $this->assertSame(file_get_contents($this->fixtureFilePath),
            file_get_contents($this->vfs->getChild($this->generatedFilePath)->url()));
    }
}