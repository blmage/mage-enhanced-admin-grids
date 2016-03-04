[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mage-eag/mage-enhanced-admin-grids/badges/quality-score.png?b=1.0.0-wip-edge)](https://scrutinizer-ci.com/g/mage-eag/mage-enhanced-admin-grids/?branch=1.0.0-wip-edge) [![Build Status](https://scrutinizer-ci.com/g/mage-eag/mage-enhanced-admin-grids/badges/build.png?b=1.0.0-wip)](https://scrutinizer-ci.com/g/mage-eag/mage-enhanced-admin-grids/build-status/1.0.0-wip)

# Enhanced Admin Grids
## Version 1.0.0 (work-in-progress / edge)

_This version includes the latest changes which are considered too experimental to be featured in the [base work-in-progress branch](https://github.com/mage-eag/mage-enhanced-admin-grids/tree/1.0.0-wip). As such, it should only be used for testing purposes._

### Final steps before beta release
- [ ] last waves of code refactoring/cleanup and complexity reduction (focus on [Scrutinizer hot spots](https://scrutinizer-ci.com/g/mage-eag/mage-enhanced-admin-grids/code-structure/1.0.0-wip/hot-spots))
- [ ] **editor system refactoring** :
    - [X] separate responsibilities into different models, introduce callbacks
    - [X] implement custom columns editability
    - [X] implement order address columns editability
    - [X] implement inventory columns editability
    - [ ] create a spreadsheet summarizing the compatibility of each editor across the different Magento versions (use three different states : "untested", "tested and functional", "tested with problems" - provide links to the related issues -)
    - [ ] start filling up the compatibility spreadsheet
- [ ] **JS code refactoring** :
    - [ ] remove `CDATA` sections
    - [ ] refactor and optimize code when possible
    - [ ] review the code style (follow some best practices)
    - [ ] ~~write comments (use [JSDoc](http://usejsdoc.org/index.html))~~ **(probably not worth the time)**
    - [ ] implement an object manager, to remove as much clutter as possible from the global scope, and automatically cleanup unneeded/overridable objects (especially for Ajax grids)
- [X] move the columns list form to dedicated window, as for the other forms (avoid cluttering any external wrapping form with a lot of parameters)
- [X] rework the profiles bar so that the number of displayed profiles is adapted to the available width
- [ ] rework the "Access All" profiles permission (make all the profiles be available from everywhere, except in the bar)
- [ ] ~~rework the sales items columns (implement the advanced text filter, improve their extensibility, and allow to display a customizable value when exported)~~ **(later)**