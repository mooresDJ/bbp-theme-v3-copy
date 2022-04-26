


UPDATE kmt_options SET option_value = replace(option_value, 'https://bbptest.local.local:8890/', 'https://bbptest.local:8890') WHERE option_name = 'home' OR option_name = 'siteurl';

UPDATE kmt_posts SET guid = replace(guid, 'https://bbptest.local.local:8890/','https://bbptest.local:8890');

UPDATE kmt_posts SET post_content = replace(post_content, 'https://bbptest.local.local:8890/', 'https://bbptest.local:8890');




UPDATE kmt_posts SET post_excerpt = replace(post_excerpt, 'https://bbptest.local.local:8890/', 'https://bbptest.local:8890');



UPDATE kmt_posts SET post_excerpt = replace(post_excerpt, 'https://bbptest.local.local:8890/', 'https://bbptest.local:8890');

UPDATE kmt_postmeta SET meta_value = replace(meta_value, 'https://bbptest.local.local:8890/', 'https://bbptest.local:8890');