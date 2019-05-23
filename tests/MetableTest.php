<?php
namespace Jobcerto\Metable\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Jobcerto\Metable\Tests\Models\Post;
use Jobcerto\Metable\Tests\Models\Tag;
use Jobcerto\Metable\Tests\Models\TagMeta;

class MetableTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->post = Post::create([
            'title' => 'Some post Title',
        ]);

        $this->tags = ['foo', 'bar', 'baz'];

    }

    /** @test */
    public function it_can_create_metas()
    {

        $this->post->meta->set('tags', $this->tags);

        $this->assertInstanceOf(Collection::class, $this->post->meta->all());

        $this->assertCount(1, $this->post->meta->all());
    }

    /** @test */
    public function it_can_transform_values()
    {

        $this->post->meta->set('tags', $this->tags);

        $tags = $this->post->meta->get('tags', function ($tags) {
            return $tags;
        });

        $this->assertEquals($tags, $this->tags);
    }

    /** @test */
    public function it_can_ignore_transformation_of_values()
    {

        $this->post->meta->set('tags', $this->tags);

        $tags = $this->post->meta->get('tags', function ($tags) {
            return [];
        }, true);

        $this->assertEquals($tags, $this->tags);
    }

    /** @test */
    public function it_can_transform_nested_values()
    {

        $this->post->meta->set('countries', ['brasil' => ['RS', 'SP', 'SC']]);

        $states = $this->post->meta->get('countries.brasil', function ($states) {
            return collect($states);
        });

        $rs = $this->post->meta->get('countries.brasil.0', function ($rs) {
            return $rs . '-transformed';
        });

        $lowercase = $this->post->meta->get('countries.brasil.0', function ($rs) {
            return strtolower($rs);
        });

        $this->assertInstanceOf(Collection::class, $states);

        $this->assertEquals('RS-transformed', $rs);
        $this->assertEquals('rs', $lowercase);
    }

    /** @test */
    public function it_can_assin_a_default_value_when_not_find_the_meta()
    {
        $meta = $this->post->meta->get('fake-meta', function () {
            return [];
        });

        $this->assertIsArray($meta);
    }

    /** @test */
    public function it_can_find_a_value_using_get_and_return_default()
    {
        $this->post->meta->set('tags', $this->tags);

        $this->assertIsArray($this->post->meta->get('tags.4', []));

    }

    /** @test */
    public function it_can_update_all_attributes()
    {

        $this->post->meta->set('tags', $this->tags);

        $newTags = ['new foo', 'new bar', 'new baz'];

        $this->assertSame($newTags, $this->post->meta->set('tags', $newTags));
    }

    /** @test */
    public function it_returns_null_when_meta_doesnt_exists()
    {
        $this->assertNull($this->post->meta->get('unknown-meta'));

        $this->assertNull($this->post->meta->get('unknown-meta.some-other-value'));
    }

    /** @test */
    public function it_can_find_a_single_meta()
    {

        $this->post->meta->set('tags', $this->tags);

        $this->assertEquals($this->tags, $this->post->meta->get('tags'));
        $this->assertEquals($this->tags[0], $this->post->meta->get('tags.0'));
        $this->assertEquals($this->tags[1], $this->post->meta->get('tags.1'));
        $this->assertEquals($this->tags[2], $this->post->meta->get('tags.2'));
        $this->assertNull($this->post->meta->get('tags.3'));

    }

    /** @test */
    public function it_can_assin_a_default_value_when_not_defining_an_castable_attribute()
    {

        $this->post->meta->set('tags', $this->tags);

        $this->assertEquals('default-value', $this->post->meta->get('unknown-meta', 'default-value'));

    }

    /** @test */
    public function it_can_find_a_single_meta_and_casts_to_object()
    {

        $tags = ['foo' => 'value-foo', 'bar' => 'value-bar', 'baz' => 'value-baz'];

        $this->post->meta->set('tags', $tags);

        $this->assertInstanceOf(\StdClass::class, $this->post->meta->get('tags', 'object'));

    }

    /** @test */
    public function it_forbid_create_an_unknown_meta_with_dot()
    {
        $this->expectException(\Exception::class);

        $this->post->meta->set('tags.nested-dot', $this->tags);

    }

    /** @test */
    public function it_can_find_a_single_meta_and_casts_to_collection()
    {

        $this->post->meta->set('tags', $this->tags);

        $this->assertInstanceOf(Collection::class, $this->post->meta->get('tags', 'collection'));

    }

    /** @test */
    public function it_can_find_a_single_meta_and_casts_to_boolean()
    {

        $this->post->meta->set('isSubscribed', true);

        $this->assertIsBool($this->post->meta->get('isSubscribed', 'boolean'));

    }

    /** @test */
    public function it_can_find_many_metas()
    {

        $favorites = ['favorite-01', 'favorite-02', 'favorite-03'];

        $this->post->meta->set('tags', $this->tags);

        $this->post->meta->set('favorites', $favorites);

        $findMany = $this->post->meta->only('tags', 'favorites', 'unknown-meta');

        $this->assertArrayHasKey('tags', $findMany);

        $this->assertArrayHasKey('favorites', $findMany);

        $this->assertNull($findMany['unknown-meta']);

    }

    /** @test */
    public function it_can_find_a_value_using_search()
    {

        $this->post->meta->set('tags', $this->tags);

        $this->assertEquals($this->post->fresh()->meta->get('tags'), $this->post->fresh()->meta->get('tags'));
    }

    /** @test */
    public function it_can_check_if_has_one_meta()
    {
        $this->post->meta->set('tags', $this->tags);

        $this->assertTrue($this->post->meta->has('tags'));

        $this->assertFalse($this->post->meta->has('fake-tag'));

    }

    /** @test */
    public function it_can_delete_a_single_meta()
    {

        $this->post->meta->set('tags', $this->tags);

        $this->post->meta->delete('tags');

        $this->assertCount(0, $this->post->meta->all());

    }

    /** @test */
    public function it_convert_all_meta_to_array()
    {

        $this->post->meta->set('tags', $this->tags);
        $this->post->meta->set('other-tags', $this->tags);

        $this->assertCount(2, $this->post->meta->toArray());
    }

    /** @test */
    public function it_can_find_value_in_meta_using_dot_notation()
    {

        $attributes = [
            'br' => 'brasil',
            'eu' => 'Estados Únidos',
        ];

        $this->post->meta->set('countries', $attributes);

        $this->assertEquals('brasil', $this->post->meta->get('countries.br'));

        $this->assertEquals('Estados Únidos', $this->post->meta->get('countries.eu'));

    }

    /** @test */
    public function it_return_the_default_value_or_null_when_search_inside_meta()
    {

        $attributes = [
            'br' => 'brasil',
            'eu' => 'Estados Únidos',
        ];

        $this->post->meta->set('countries', $attributes);

        $this->assertNull($this->post->meta->get('countries.something-that-is-fake'));

        $this->assertEquals('my-custom-value', $this->post->meta->get('countries.something-that-is-fake', 'my-custom-value'));
    }

    /** @test */
    public function a_metable_model_can_use_an_custom_model()
    {
        $tag = Tag::create(['title' => 'some Tag']);

        $this->assertInstanceOf(TagMeta::class, $tag->metable()->getModel());
    }

    /** @test */
    public function it_can_replace_any_value_inside_a_meta()
    {
        $countries = [
            'br' => 'brazil',
            'eu' => 'estados unidos',
        ];

        $this->post->meta->set('countries', $countries);

        $this->post->meta->replace('countries.br', 'novo valor');

        $this->assertEquals('novo valor', $this->post->meta->get('countries.br'));
    }

    /** @test */
    public function it_can_replace_an_unknown_value_inside_a_meta()
    {

        $countries = [
            'br' => 'brazil',
            'eu' => 'estados unidos',
        ];

        $this->post->meta->set('countries', $countries);

        $this->post->meta->replace('countries.ru', 'Russia');

        $this->assertEquals('Russia', $this->post->meta->get('countries.ru'));

        $countriesWithNewAddedValue = [
            'br' => 'brazil',
            'eu' => 'estados unidos',
            'ru' => 'Russia',
        ];

        $this->assertSame($countriesWithNewAddedValue, $this->post->meta->get('countries'));
    }

    /** @test */
    public function it_throw_exception_when_tries_to_replace_whitout_one_dot()
    {
        $this->expectException(\Exception::class);

        $countries = [
            'br' => 'brazil',
            'eu' => 'estados unidos',
        ];

        $this->post->meta->set('countries', $countries);

        $this->post->meta->replace('countries', 'novo valor');
    }

}
