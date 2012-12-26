<?php

namespace Exporter\Test\Source;

use Exporter\Writer\SitemapWriter;
use SimpleXMLElement;

class SitemapWriterTest extends \PHPUnit_Framework_TestCase
{
    protected $folder;

    public function setUp()
    {
        $this->folder = sys_get_temp_dir().'/sonata_exporter_test';

        $this->tearDown();

        mkdir($this->folder);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testNonExistentFolder()
    {
        $writer = new SitemapWriter('booo', 'sitemap_%04d.xml');
        $writer->open();
    }

    public function testSimpleWrite()
    {
        $writer = new SitemapWriter($this->folder, 'sitemap_%04d.xml');
        $writer->open();
        $writer->write(array(
            'url'     => 'http://sonata-project.org/bundle',
            'lastmod' => 'now',
            'change'  => 'daily'
        ));
        $writer->close();

        $generatedFiles = $this->getFiles();

        $this->assertEquals(2, count($generatedFiles));

        // this will throw an exception if the xml is invalid
        new SimpleXMLElement(file_get_contents($generatedFiles[1]));

        $expected = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>http://sonata-project.org/bundle</loc><lastmod>2012-12-26</lastmod><changefreq>weekly</changefreq><priority>0.5</priority></url></urlset>';

        $this->assertEquals($expected, file_get_contents($generatedFiles[1]));
    }

    public function testLimitSize()
    {
        $writer = new SitemapWriter($this->folder, 'sitemap_%04d.xml');
        $writer->open();

        foreach (range(0, SitemapWriter::LIMIT_SIZE / 8196) as $i) {
            $writer->write(array(
                'url'     => str_repeat('x', 8196),
                'lastmod' => 'now',
                'change'  => 'daily'
            ));
        }
        $writer->close();

        $generatedFiles = $this->getFiles();

        $this->assertEquals(3, count($generatedFiles));

        // this will throw an exception if the xml is invalid
        new SimpleXMLElement(file_get_contents($generatedFiles[1]));
        new SimpleXMLElement(file_get_contents($generatedFiles[2]));

        $info = stat($generatedFiles[1]);

        $this->assertLessThan(SitemapWriter::LIMIT_SIZE, $info['size']);
    }

    public function testLimitUrl()
    {
        $writer = new SitemapWriter($this->folder, 'sitemap_%04d.xml');
        $writer->open();

        foreach (range(1, SitemapWriter::LIMIT_URL + 1) as $i) {
            $writer->write(array(
                'url'     => str_repeat('x', 40),
                'lastmod' => 'now',
                'change'  => 'daily'
            ));
        }
        $writer->close();

        $generatedFiles = $this->getFiles();

        $this->assertEquals(3, count($generatedFiles));

        // this will throw an exception if the xml is invalid
        $file1 = new SimpleXMLElement(file_get_contents($generatedFiles[1]));
        $file2 = new SimpleXMLElement(file_get_contents($generatedFiles[2]));

        $info = stat($generatedFiles[0]);

        $this->assertLessThan(SitemapWriter::LIMIT_SIZE, $info['size']);
        $this->assertEquals(SitemapWriter::LIMIT_URL, count($file1->children()));
        $this->assertEquals(1, count($file2->children()));
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        $files = glob($this->folder.'/*.xml');

        sort($files);

        return $files;
    }

    public function tearDown()
    {

        foreach($this->getFiles() as $file) {
            unlink($file);
        }

        if (is_dir($this->folder)) {
            rmdir($this->folder);
        }
    }
}
