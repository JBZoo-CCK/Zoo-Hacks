# YOOtheme Zoo Performance Hacks for Joomla! CMS

**ONLY ON YOUR OWN RISK !!!**

## ZOO Fixes

#### Performance
 * Disable updates hit counter ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L168))
 * Disable checks for `publish_up`, `publish_down` and user ACL
    ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L234),
     [#2](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L280),
     [#3](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L335),
     [#4](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L379),
     [#5](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L422),
     [#6](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L465),
     [#7](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L514),
     [#8](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L542),
     [#9](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L611),
     [#10](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L629),
     [#11](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/item.php#L691),
     [#12](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/comment.php#L136),
     [#13](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/tag.php#L48),
     [#14](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/tables/category.php#L199))
 * Disable checking fs permissions for ZOO Control Panel ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/zoo.php#L44))
 * Don't create new instance on each `$this-app->data->create()` ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/framework/helpers/data.php#L41))
 * Static cache for string sluggify ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/helpers/string.php#L72))
 * Separated thumbs paths for slow FS system ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/helpers/zoo.php#L132))
 * Don't check thumb modified date ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/helpers/zoo.php#L150))
 * Delete preview mode in my-submissions ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/components/com_zoo/partials/_mysubmissions.php#67))

#### Others
 * Change image quality for ZOO resizer; 95 ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/zoo-patch/administrator/components/com_zoo/helpers/imagethumbnail.php#L141))

## JBZoo Fixes

#### Others
 * Delete Website input field from Zoo comments form
    ([#1](https://github.com/JBZoo/Zoo-Hacks/blob/master/jbzoo-patch/media/zoo/applications/jbuniversal/templates/bootstrap/renderer/respond/_default.php#L103),
     [#2](https://github.com/JBZoo/Zoo-Hacks/blob/master/jbzoo-patch/media/zoo/applications/jbuniversal/templates/catalog/renderer/respond/_default.php#L103),
     [#3](https://github.com/JBZoo/Zoo-Hacks/blob/master/jbzoo-patch/media/zoo/applications/jbuniversal/templates/uikit/renderer/respond/_default.php#L103))

## How to install
Just unpack arch to root folder of your website. Replace files!

## Links
 * [Zoo-Changelog](https://github.com/JBZoo/Zoo-Changelog)
 * [Russian forum](http://forum.jbzoo.com/files/file/58-hak-pozvolyaet-umenshit-nagruzku-na-bd-i-fs/)

## License
[GNU/GPL](http://www.gnu.org/licenses/gpl.html)
