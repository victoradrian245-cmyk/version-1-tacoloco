import matplotlib.pyplot as plt
import numpy as np
from sklearn.neighbors import KNeighborsClassifier
from sklearn.datasets import make_blobs

# 1. Generar datos de ejemplo (Simulación de un dataset)
# Creamos 2 grupos de datos (clusters) con un poco de dispersión
X, y = make_blobs(n_samples=20, centers=2, random_state=42, cluster_std=1.5)

# 2. Definir un "Punto Nuevo" misterioso que queremos clasificar
nuevo_punto = np.array([[0, 2]])

# 3. Configurar el modelo K-NN
# n_neighbors=3: Buscará los 3 vecinos más cercanos
# metric='euclidean': Esta es la parte clave, usa la fórmula de distancia euclidiana
knn = KNeighborsClassifier(n_neighbors=3, metric='euclidean')

# 4. Entrenar (En KNN esto es solo "memorizar" los puntos)
knn.fit(X, y)

# 5. Calcular distancias y predecir
# kneighbors devuelve las distancias y los índices de los puntos más cercanos
distancias, indices = knn.kneighbors(nuevo_punto)
prediccion = knn.predict(nuevo_punto)

# --- RESULTADOS ---
print(f"Coordenadas del nuevo punto: {nuevo_punto[0]}")
print(f"Distancias Euclidianas a los 3 vecinos más cercanos: {distancias[0]}")
print(f"Clase Predicha: {prediccion[0]} (0=Azul, 1=Naranja)")

# --- VISUALIZACIÓN ---
plt.figure(figsize=(8, 6))
# Puntos existentes
plt.scatter(X[y == 0][:, 0], X[y == 0][:, 1], color='blue', label='Clase 0', s=100, alpha=0.6)
plt.scatter(X[y == 1][:, 0], X[y == 1][:, 1], color='orange', label='Clase 1', s=100, alpha=0.6)
# Punto nuevo
plt.scatter(nuevo_punto[:, 0], nuevo_punto[:, 1], color='red', label='Punto Nuevo', marker='*', s=300, zorder=10)

# Dibujar líneas (Distancia Euclidiana) a los vecinos
vecinos = X[indices[0]]
for vecino in vecinos:
    plt.plot([nuevo_punto[0, 0], vecino[0]], [nuevo_punto[0, 1], vecino[1]], 'k--', alpha=0.5)

plt.title(f'Visualización de K-NN (K=3)\nLas líneas punteadas son las Distancias Euclidianas medidas')
plt.legend()
plt.grid(True, alpha=0.3)
plt.show()