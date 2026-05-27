  <?php

  function load_books_from_xml() {
    $xml_path = __DIR__ . '/../xml/books.xml';

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    if (!file_exists($xml_path)) {
      return [];
    }

    if (!$dom->load($xml_path)) {
      return [];
    }

    $books = [];
    $book_nodes = $dom->getElementsByTagName('book');

    foreach ($book_nodes as $book_node) {
      $books[] = [
        'id' => get_xml_value($book_node, 'id'),
        'title' => get_xml_value($book_node, 'title'),
        'author' => get_xml_value($book_node, 'author'),
        'genre' => get_xml_value($book_node, 'genre'),
        'category' => get_xml_value($book_node, 'category'),
        'year' => (int)get_xml_value($book_node, 'year'),
        'copies' => (int)get_xml_value($book_node, 'copies'),
        'available' => (int)get_xml_value($book_node, 'available'),
        'color' => get_xml_value($book_node, 'color'),
      ];
    }

    return $books;
  }

  function get_xml_value($parent, $tag_name) {
    $nodes = $parent->getElementsByTagName($tag_name);

    if ($nodes->length === 0) {
      return '';
    }

    return trim($nodes->item(0)->nodeValue);
  }

  function find_book_from_xml($book_id) {
    $books = load_books_from_xml();

    foreach ($books as $book) {
      if ((string)$book['id'] === str_pad((string)$book_id, 2, '0', STR_PAD_LEFT)) {
        return $book;
      }
    }

    return null;
  }
  ?>