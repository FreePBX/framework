<?php
return array(
    'grabber' => array(
        '%.*%' => array(
            'test_url' => 'http://www.economist.com/blogs/buttonwood/2017/02/mixed-signals?fsrc=rss',
            'body' => array(
                '//div[@class="blog-post__inner"]',
            ),
            'strip' => array(
                '//aside',
                '//div[@class="blog-post__asideable-wrapper"]',
                '//div[@class="video-player__wrapper"]',
                '//div[contains(@class,"latest-updates-panel__container")]',
                '//div[contains(@class,"blog-post__siblings-list-aside")]',
                '//div[contains(@class,"blog-post__asideable-content")]'
            ),
        ),
    ),
);
