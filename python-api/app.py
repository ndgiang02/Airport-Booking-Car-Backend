from flask import Flask, request, jsonify
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler
import numpy as np
from datetime import datetime
import matplotlib.pyplot as plt
from io import BytesIO
import base64

app = Flask(__name__)

def calculate_wcss(coordinates_and_time, max_k=10):
    wcss = []
    for k in range(1, min(max_k, len(coordinates_and_time)) + 1):
        kmeans = KMeans(n_clusters=k)
        kmeans.fit(coordinates_and_time)
        wcss.append(kmeans.inertia_)
    return wcss

def find_elbow(wcss):
    if len(wcss) > 1:
        diffs = np.diff(wcss)
        elbow_point = np.argmax(diffs[1:] < 0.1 * diffs[0]) + 2
        return min(elbow_point, len(wcss))
    return 1

def plot_elbow_method(wcss):
    plt.figure(figsize=(8, 5))
    plt.plot(range(1, len(wcss) + 1), wcss, marker='o')
    plt.title('Elbow Method')
    plt.xlabel('Number of clusters')
    plt.ylabel('WCSS')
    plt.grid(True)

    img = BytesIO()
    plt.savefig(img, format='png')
    img.seek(0)
    plot_url = base64.b64encode(img.getvalue()).decode('utf8')
    plt.close()
    return plot_url

def plot_elbow_method(wcss):
    plt.figure(figsize=(8, 5))
    plt.plot(range(1, len(wcss) + 1), wcss, marker='o')
    plt.title('Elbow Method')
    plt.xlabel('Number of clusters')
    plt.ylabel('WCSS')
    plt.grid(True)

    img = BytesIO()
    plt.savefig(img, format='png')
    img.seek(0)
    plot_url = base64.b64encode(img.getvalue()).decode('utf8')
    plt.close()
    return plot_url

def plot_clusters(coordinates_and_time, labels, n_clusters):
    plt.figure(figsize=(10, 6))
    # Vẽ các điểm dữ liệu theo cụm
    for cluster_id in range(n_clusters):
        cluster_points = coordinates_and_time[labels == cluster_id]
        plt.scatter(cluster_points[:, 0], cluster_points[:, 1], label=f'Cluster {cluster_id}')
    
    # Đặt tiêu đề và nhãn trục
    plt.title('Cluster Visualization')
    plt.xlabel('Latitude')
    plt.ylabel('Longitude')
    plt.legend()
    plt.grid(True)
    
    # Lưu biểu đồ thành ảnh base64
    img = BytesIO()
    plt.savefig(img, format='png')
    img.seek(0)
    plot_url = base64.b64encode(img.getvalue()).decode('utf8')
    plt.close()
    return plot_url

@app.route('/cluster', methods=['POST'])
def cluster():
    try:
        data = request.get_json()

        # Cluster by LatLong , Time
        coordinates_and_time = np.array([
            [
                loc['from_lat'], loc['from_lng'],
                loc['to_lat'], loc['to_lng'],
                datetime.strptime(loc['scheduled_time'], "%Y-%m-%d %H:%M").timestamp()
            ]
            for loc in data['trips']
        ])
        
        scaler = StandardScaler()
        coordinates_and_time = scaler.fit_transform(coordinates_and_time)
         
        # Caculator WCSS and Find n_clusters
        wcss = calculate_wcss(coordinates_and_time, max_k=10)
        elbow_plot_url = plot_elbow_method(wcss)
        n_clusters = find_elbow(wcss)

        # CLuster by KMeans
        kmeans = KMeans(n_clusters=n_clusters)
        kmeans.fit(coordinates_and_time)
        clusters = kmeans.labels_.tolist()

        final_clusters = clusters.copy()
        vehicle_types_per_cluster = {}

         # Cluster vehicle type
        current_index = max(final_clusters) + 1
        for cluster_id in set(clusters):
            vehicle_types_in_cluster = set([
                data['trips'][i]['vehicle_type']
                for i in range(len(data['trips'])) if final_clusters[i] == cluster_id
            ])
            vehicle_types_per_cluster[cluster_id] = list(vehicle_types_in_cluster)

            if len(vehicle_types_in_cluster) > 1:
                for i in range(len(data['trips'])):
                    if final_clusters[i] == cluster_id and data['trips'][i]['vehicle_type'] != list(vehicle_types_in_cluster)[0]:
                        final_clusters[i] = current_index
                        vehicle_types_per_cluster[current_index] = [data['trips'][i]['vehicle_type']]
                current_index += 1

        # Cluster theo seating capacity
        final_clusters_split_by_capacity = final_clusters.copy()

        for cluster_id in set(final_clusters):
            trips_in_cluster = [i for i in range(len(data['trips'])) if final_clusters[i] == cluster_id]
            
            current_cluster_passenger_count = 0
            for trip_idx in trips_in_cluster:
                trip = data['trips'][trip_idx]
                current_cluster_passenger_count += trip['passenger_count']

                if current_cluster_passenger_count > trip['seating_capacity']:
                    final_clusters_split_by_capacity[trip_idx] = current_index
                    
                    original_vehicle_type = vehicle_types_per_cluster.get(cluster_id, [])
                    vehicle_types_per_cluster[current_index] = original_vehicle_type.copy()
                    
                    current_cluster_passenger_count = trip['passenger_count'] 
                    current_index += 1
                    
                    
        cluster_plot_url = plot_clusters(scaler.inverse_transform(coordinates_and_time), np.array(final_clusters), len(set(final_clusters)))

        result = {
            "status": "success",
            "n_clusters": len(set(final_clusters_split_by_capacity)),
            "clusters": [int(c) for c in final_clusters_split_by_capacity],
            "elbow_plot": elbow_plot_url,
            "cluster_plot": cluster_plot_url,
            "vehicle_types": {int(k): v for k, v in vehicle_types_per_cluster.items()} 
        }

        return jsonify(result)

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
