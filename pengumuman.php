<?php
// Front controller for Pengumuman page so that it can be accessed via /pengumuman.php
// Simply delegate to the actual controller following MVC conventions.
// All heavy lifting (database queries, rendering, layout) is handled in the target controller.
require_once __DIR__ . '/modules/admin/controllers/pengumuman.php';
