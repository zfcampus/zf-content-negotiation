<?php

namespace ZFTest\ContentNegotiation\TestAsset;

class ModelWithJson implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return array('foo' => 'bar');
    }
}
