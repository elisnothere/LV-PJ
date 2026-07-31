# Tareas para el proyecto

### Mejoras de producto/UI
- Que en el carrito, se vea la foto del producto
- Que en el preview del pedido, tambien se vea la foto.
- Agregar un campo de "precio promocional" y ese campo debe ser usado cuando haces la compra. Y mostrarse en la UI
- Agregar paginacion
- Agregar costo de envio por zonas (ciudades). Esto debe ser configurable desde algun lado.
- Se puede buscar en tienda tambien. Envio = 0.
- Vamos a crear un nuevo layout, no muy trabajado tampoco, pero que sea diferente al principal. Este layout se usara en "categorias, catalogo, y contacto". Asi podemos separar el "dashboard" de la UI.
- Hacer una funcionalidad de seguimiento de pedido. El usuario debe poder entrar a una seccion y ver el pedido que hizo. En este pedido, va a poder ver cuando (fecha y hora) compro, cuando paso a estado en proceso, enviado, recibido, etc.
- Si un producto no tiene stock, se va a mostrar, pero no se puede comprar. Si no hay stock, se muestra un input donde podes poner tu email, y te registras en una lista que te avisa cuando ese producto vuelve a tener stock. Esto se hace por medio de un email. PD: esto es medio hendy. (preguntale a la AI por observers).
- poder marcar un producto como oferta. Las ofertas se pueden buscar en el catalogo.
- 

### Cosas que agregar
- Los productos, pueden tener mas de una imagen
- Mejorar separacion de responsabilidades:
    - dejar controllers solo para validaciones y respuestas
    - crear servicios que contengan business logic
- Mejorar error handling
- Separar seeders en clases propias
- Crear tests
- Traer productos de una API
    - Traer productos de esta API: https://kolzsticks.github.io/Free-Ecommerce-Products-Api/main/products.json, adaptarlos a tu esquema y guardarlos. Esto se debe hacer en un background job.
    - Traer productos de aca tambien, y hacerlo mismo: https://api.escuelajs.co/api/v1/products. Hay que guardar las fotos en tu local.

- Las categorias deben ser una entidad aparte, es decir, crear tabla, models, etc.