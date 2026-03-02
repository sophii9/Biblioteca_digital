<?php
    require __DIR__ . '/composer/ratchet/vendor/autoload.php';

/**
 * MyChat - Servidor de Chat en Tiempo Real
 * Modificado para soportar mensajes normales y mensajes de "Vista Previa de Libro"
 *
 * TIPOS DE MENSAJES (campo "tipo" en el JSON):
 *   - "mensaje"       → mensaje de chat normal, se envía a todos EXCEPTO el remitente
 *   - "libro_preview" → vista previa de libro con imágenes, se envía a TODOS (incluyendo remitente)
 *                       para que el que lo comparte también vea la confirmación
 */
class MyChat implements Ratchet\MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(Ratchet\ConnectionInterface $conn) {
        // Se conecta un nuevo cliente
        $this->clients->attach($conn);
        echo "Nueva conexión: ({$conn->resourceId})\n";
    }

    public function onMessage(Ratchet\ConnectionInterface $from, $msg) {
        // Intentamos decodificar el JSON para ver el tipo de mensaje
        $datos = json_decode($msg, true);
        $tipo = isset($datos['tipo']) ? $datos['tipo'] : 'mensaje';

        foreach ($this->clients as $client) {
            if ($tipo === 'libro_preview') {
                // Vista previa de libro: se envía a TODOS los conectados,
                // incluido quien lo compartió (para que vea confirmación)
                $client->send($msg);
            } else {
                // Mensaje normal de chat: se envía a todos MENOS al remitente
                if ($from !== $client) {
                    $client->send($msg);
                }
            }
        }
    }

    public function onClose(Ratchet\ConnectionInterface $conn) {
        // Se desconecta un cliente
        $this->clients->detach($conn);
        echo "Conexión cerrada: ({$conn->resourceId})\n";
    }

    public function onError(Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

    // Ejecutar el servidor WebSocket en el puerto 8080
    $app = new Ratchet\App('localhost', 8080);
    $app->route('/chat', new MyChat, array('*'));
    $app->route('/echo', new Ratchet\Server\EchoServer, array('*'));
    $app->run();